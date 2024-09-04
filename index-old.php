<?php

require_once './vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

set_time_limit(0);

$urls = [
	'https://wigconference2023.nilds.gov.ng',
	'https://democracyradio.ng',
	'https://nilds.gov.ng/',
	'https://postgraduate.nilds.gov.ng/',
	'https://library.nilds.gov.ng/',
	'https://recurrent.ng/',
	'https://current.ng/',
	'https://www.c80.ng/',
	'https://c80.io/',
	'https://c80.io/',
];

$apiKey = 'XC632VNNZE2U0DMOMIUNG5QDRA1RBY796Y99G6ALXBXDA46N9VDXMXIKUMOC25NHRALXWZJW1HR5127W';
$screenshotsDir = 'screenshots';
if (!file_exists($screenshotsDir)) {
	mkdir($screenshotsDir, 0777, true);
}

function getHttpStatusMessage ($code): string
{
	$messages = [

		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',

		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Payload Too Large',
		414 => 'URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I’m a teapot',
		421 => 'Misdirected Request',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		451 => 'Unavailable For Legal Reasons',

		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		510 => 'Not Extended',
		511 => 'Network Authentication Required'
	];

	return $messages[$code] ?? 'Unknown Status';
}

function home_base_url (): string
{
	$base_url = (isset($_SERVER['HTTPS']) &&
		$_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
	$tmpURL = dirname(__FILE__);
	$tmpURL = str_replace(chr(92), '/', $tmpURL);
	$tmpURL = str_replace($_SERVER['DOCUMENT_ROOT'], '', $tmpURL);
	$tmpURL = ltrim($tmpURL, '/');

	$tmpURL = rtrim($tmpURL, '/');

	if (strpos($tmpURL, '/')) {
		$tmpURL = explode('/', $tmpURL);
		$tmpURL = $tmpURL[0];
	}

	if ($tmpURL !== $_SERVER['HTTP_HOST'])
		$base_url .= $_SERVER['HTTP_HOST'] . '/' . $tmpURL . '/';
	else
		$base_url .= $tmpURL . '/';

	return $base_url;
}

function monitorUrl ($url, $apiKey, $screenshotsDir): array
{
	$response = [
		'url' => $url,
		'status' => 'No issues detected'
	];

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_NOBODY, false);

	$startTime = microtime(true);
	$htmlContent = curl_exec($ch);
	$endTime = microtime(true);

	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
	$downloadSpeed = curl_getinfo($ch, CURLINFO_SPEED_DOWNLOAD);
	$totalTime = $endTime - $startTime;

	curl_close($ch);

	if ($httpCode >= 400) {
		$htmlFilePath = 'page_content_' . date('Ymd_His') . '.html';
		file_put_contents($htmlFilePath, $htmlContent);

		$host = home_base_url();
		$screenshotFilePath = $screenshotsDir . '/screenshot_' . date('Ymd_His') . '.png';
		$screenshotFileUrl = $host . '/' . $screenshotFilePath;

		$ch = curl_init();
		$baseUrl = 'https://app.scrapingbee.com/api/v1/';
		$params = [
			'api_key' => $apiKey,
			'url' => $url,
			'screenshot_full_page' => true
		];
		$query = http_build_query($params);

		curl_setopt($ch, CURLOPT_URL, $baseUrl . '?' . $query);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$screenshotData = curl_exec($ch);

		if (!$screenshotData) {
			$response['error'] = 'Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch);
			return $response;
		}

		file_put_contents($screenshotFilePath, $screenshotData);
		curl_close($ch);

		$templateFilePath = 'report.html';
		$templateContent = file_get_contents($templateFilePath);

		$reportContent = str_replace(
			['{{url}}', '{{httpCode}}', '{{httpMessage}}', '{{timestamp}}', '{{contentLength}}', '{{downloadSpeed}}', '{{totalTime}}', '{{screenshotUrl}}'],
			[$url, $httpCode, getHttpStatusMessage($httpCode), date('Y-m-d H:i:s'), ($contentLength ? number_format($contentLength) . ' bytes' : 'N/A'), ($downloadSpeed ? number_format($downloadSpeed) . ' bytes/sec' : 'N/A'), number_format($totalTime, 2) . ' seconds', $screenshotFileUrl],
			$templateContent
		);

		$reportFile = 'report_' . date('Ymd_His') . '.html';
		file_put_contents($reportFile, $reportContent);

		$mail = new PHPMailer(true);
		try {
			$mail->isSMTP();
			$mail->Host = 'smtp.gmail.com';
			$mail->SMTPAuth = true;
			$mail->Username = 'kokocampusofficial@gmail.com';
			$mail->Password = 'fgjukvzzflgvzecf';
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port = 587;

			$mail->setFrom('kokocampusofficial@gmail.com', 'Monitoring tool');
			$mail->addAddress('Bellohabeeb565@gmail.com', 'Cyber');
			$mail->addAddress('eminibest@gmail.com', 'Cyber');

			$mail->addAttachment($screenshotFilePath);

			$mail->isHTML(true);
			$mail->Subject = "Monitoring Report for {$url}";
			$mail->Body = $reportContent;

			$mail->send();

			$response['status'] = 'Message has been sent';
			$response['reportFile'] = $reportFile;
			$response['htmlFilePath'] = $htmlFilePath;
			$response['screenshotFilePath'] = $screenshotFilePath;

			unlink($htmlFilePath);
			unlink($reportFile);
			unlink($screenshotFilePath);
		} catch (Exception $e) {
			$response['error'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
		}
	}

	return $response;
}

while (true) {
	$responses = [];
	foreach ($urls as $url) {
		$result = monitorUrl($url, $apiKey, $screenshotsDir);
		$responses[] = $result;
	}

	echo json_encode($responses) . "\n";

	sleep(300);
}
