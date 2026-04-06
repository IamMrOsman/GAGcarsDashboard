<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventMessageMail extends Mailable
{
	use Queueable, SerializesModels;

	public function __construct(
		public string $mailSubject,
		public string $bodyText,
	) {}

	public function envelope(): Envelope
	{
		return new Envelope(
			subject: $this->mailSubject,
		);
	}

	public function content(): Content
	{
		return new Content(
			view: 'emails.event-message',
			with: [
				'bodyText' => $this->bodyText,
			],
		);
	}

	public function attachments(): array
	{
		return [];
	}
}
