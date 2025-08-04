<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class ItemField extends Model
{
	protected $guarded = [];

	protected $casts = [
		'options' => 'array',
	];

	protected $appends = ['options_keys'];

	public function getOptionsKeysAttribute()
	{
		if ($this->options && is_array($this->options)) {
			return array_keys($this->options);
		}
		return [];
	}

	protected static function booted()
	{
		static::created(function ($field) {
			static::addOrUpdateColumn($field);
		});

		static::updated(function ($field) {
			static::addOrUpdateColumn($field, true);
		});

		static::deleting(function ($field) {
			static::dropColumn($field);
		});
	}

	protected static function addOrUpdateColumn($field, $isUpdate = false)
	{
		$table = 'items';
		$column = $field->name;
		$type = static::getColumnType($field);

		if (!Schema::hasColumn($table, $column)) {
			Schema::table($table, function (Blueprint $table) use ($field, $type, $column) {
				static::addColumn($table, $type, $field, $column);
			});
		} elseif ($isUpdate) {
			// For update, modify the column
			Schema::table($table, function (Blueprint $table) use ($field, $type, $column) {
				static::addColumn($table, $type, $field, $column, true);
			});
		}
	}

	protected static function addColumn(Blueprint $table, $type, $field, $column, $change = false)
	{
		$col = null;
		switch ($type) {
			case 'string':
				$col = $table->string($column);
				break;
			case 'number':
				$col = $table->integer($column);
				break;
			case 'boolean':
				$col = $table->boolean($column);
				break;
			case 'json':
				$col = $table->json($column);
				break;
			case 'enum':
				$col = $table->enum($column, array_keys($field->options ?? []));
				break;
			case 'year':
				$col = $table->year($column);
				break;
			case 'date':
				$col = $table->date($column);
				break;
			case 'time':
				$col = $table->time($column);
				break;
			case 'datetime':
				$col = $table->dateTime($column);
				break;
			case 'text':
				$col = $table->text($column);
				break;
			case 'longtext':
				$col = $table->longText($column);
				break;
			default:
				$col = $table->string($column);
		}

		if ($col) {
			$col->nullable();
			if ($change) $col->change();
		}
	}



	protected static function dropColumn($field)
	{
		$table = 'items';
		$column = $field->name;
		if (Schema::hasColumn($table, $column)) {
			Schema::table($table, function (Blueprint $table) use ($column) {
				$table->dropColumn($column);
			});
		}
	}

	protected static function getColumnType($field)
	{
		return $field->type;
	}

	public function categories()
	{
		return $this->belongsToMany(Category::class);
	}
}
