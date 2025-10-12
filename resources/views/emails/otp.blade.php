<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Your OTP Code</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			line-height: 1.6;
			color: #333;
			max-width: 600px;
			margin: 0 auto;
			padding: 20px;
		}
		.otp-container {
			background-color: #f8f9fa;
			padding: 20px;
			text-align: center;
			margin: 20px 0;
		}
		.otp-code {
			font-size: 32px;
			font-weight: bold;
			color: red;
			letter-spacing: 5px;
			margin: 10px 0;
		}
		.warning {
			background-color: #fff3cd;
			border: 1px solid #ffeaa7;
			border-radius: 4px;
			padding: 15px;
			margin: 20px 0;
			color: #856404;
		}
	</style>
</head>
<body>
	<h2>Your OTP Code</h2>

	@if($userName)
		<p>Hello {{ $userName }},</p>
	@else
		<p>Hello,</p>
	@endif

	<p>You have requested a One-Time Password (OTP) for your account. Please use the following code to complete your action:</p>

	<div class="otp-container">
		<div class="otp-code">{{ $otp }}</div>
	</div>

	<div class="warning">
		<strong>Important:</strong> This code will expire in 10 minutes. Do not share this code with anyone.
	</div>

	<p>If you did not request this OTP, please ignore this email.</p>

	<p>Best regards,<br>
	GAG Cars Team</p>
</body>
</html>
