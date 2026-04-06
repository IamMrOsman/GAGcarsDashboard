<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\CategoryRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\EventMessageService;

class ItemDraftController extends Controller
{
	public function index(Request $request)
	{
		$user = $request->user();

		$drafts = Item::query()
			->where('user_id', $user->id)
			->whereIn('status', ['draft', 'pending_payment'])
			->orderByDesc('updated_at')
			->paginate(20);

		return response()->json([
			'success' => true,
			'message' => 'Drafts fetched',
			'data' => $drafts,
		], 200);
	}

	public function show(Request $request, Item $item)
	{
		$this->authorizeDraft($request, $item);

		return response()->json([
			'success' => true,
			'message' => 'Draft fetched',
			'data' => $item,
		], 200);
	}

	public function store(Request $request)
	{
		$user = $request->user();

		$data = $request->validate([
			'category_id' => ['nullable', 'integer', 'exists:categories,id'],
			'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
			'brand_model_id' => ['nullable', 'integer', 'exists:brand_models,id'],
			'name' => ['required', 'string', 'max:255'],
			'location' => ['nullable', 'string', 'max:255'],
			'price' => ['nullable'],
			'description' => ['nullable', 'string'],
			'features' => ['nullable', 'array'],
			'images' => ['nullable', 'array'],
			'draft_step' => ['nullable', 'string', 'max:100'],
			'draft_payload' => ['nullable', 'array'],
		]);

		// Generate a unique slug for drafts (user can update it later).
		$baseSlug = Str::slug($data['name']);
		$slug = $baseSlug . '-' . Str::lower((string) Str::ulid());

		$item = new Item();
		$item->fill(Arr::except($data, ['draft_payload']));
		$item->user_id = $user->id;
		$item->country_id = $user->country_id;
		$item->slug = $slug;
		$item->status = 'draft';
		$item->draft_payload = $data['draft_payload'] ?? $data;
		$item->last_saved_at = now();
		$item->expires_at = now()->addDays(30);
		$item->save();

		return response()->json([
			'success' => true,
			'message' => 'Draft created',
			'data' => $item->fresh(),
		], 201);
	}

	public function update(Request $request, Item $item)
	{
		$this->authorizeDraft($request, $item);

		$data = $request->validate([
			'category_id' => ['nullable', 'integer', 'exists:categories,id'],
			'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
			'brand_model_id' => ['nullable', 'integer', 'exists:brand_models,id'],
			'name' => ['nullable', 'string', 'max:255'],
			'slug' => ['nullable', 'string', 'max:255'],
			'location' => ['nullable', 'string', 'max:255'],
			'price' => ['nullable'],
			'description' => ['nullable', 'string'],
			'features' => ['nullable', 'array'],
			'images' => ['nullable', 'array'],
			'draft_step' => ['nullable', 'string', 'max:100'],
			'draft_payload' => ['nullable', 'array'],
		]);

		// Do not allow arbitrary status changes via draft update.
		unset($data['status']);

		$item->fill(Arr::except($data, ['draft_payload']));
		if (array_key_exists('draft_payload', $data)) {
			$item->draft_payload = $data['draft_payload'];
		}
		$item->last_saved_at = now();
		$item->expires_at = now()->addDays(30);
		$item->save();

		return response()->json([
			'success' => true,
			'message' => 'Draft updated',
			'data' => $item->fresh(),
		], 200);
	}

	public function destroy(Request $request, Item $item)
	{
		$this->authorizeDraft($request, $item);

		$item->delete();

		return response()->json([
			'success' => true,
			'message' => 'Draft deleted',
		], 200);
	}

	public function submit(Request $request, Item $item, EventMessageService $eventMessages)
	{
		$this->authorizeDraft($request, $item);

		// Require minimum data to publish.
		$missing = [];
		foreach (['category_id', 'name', 'slug', 'location'] as $key) {
			if (empty($item->{$key})) {
				$missing[] = $key;
			}
		}
		$images = $item->images ?? [];
		if (!is_array($images) || count($images) < 1) {
			$missing[] = 'images';
		}
		if ($missing !== []) {
			throw ValidationException::withMessages([
				'fields' => ['Missing required fields: ' . implode(', ', $missing)],
			]);
		}

		$user = $request->user();

		// If already published/approved/etc, return idempotently.
		if (!in_array($item->status, ['draft', 'pending_payment'], true)) {
			return response()->json([
				'success' => true,
				'message' => 'Draft already submitted',
				'data' => $item,
			], 200);
		}

		$approvalRequired = CategoryRequirement::where('category_id', $item->category_id)
			->where('country_id', $user->country_id)
			->where('require_approval', true)
			->exists();

		$paymentRequired = CategoryRequirement::where('category_id', $item->category_id)
			->where('country_id', $user->country_id)
			->where('require_payment', true)
			->exists();

		if ($paymentRequired) {
			$uploadsLeft = $user->getUploadsLeftForCategory($item->category_id);
			if ($uploadsLeft <= 0) {
				$item->status = 'pending_payment';
				$item->save();

				return response()->json([
					'success' => false,
					'message' => 'No uploads left for this category. Please purchase an upload package.',
				], 402);
			}

			$user->decrementUploadsForCategory($item->category_id);
		}

		$item->status = $approvalRequired ? 'pending_approval' : 'active';
		$item->last_saved_at = now();
		$item->save();

		$eventMessages->send('item_listed', $user, [
			'item_name' => (string) ($item->name ?? ''),
		]);

		return response()->json([
			'success' => true,
			'message' => 'Draft submitted',
			'data' => $item,
		], 200);
	}

	private function authorizeDraft(Request $request, Item $item): void
	{
		$user = $request->user();
		if ((string) $item->user_id !== (string) $user->id) {
			abort(403, 'Forbidden');
		}
	}
}

