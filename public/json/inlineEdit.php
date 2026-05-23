<?php

declare(strict_types=1);

set_include_path(get_include_path() . PATH_SEPARATOR . '../');

require_once 'includes/common.php';

use libAllure\Session;

require_once __DIR__ . '/../includes/functionality/inline_edit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    outputJson(['ok' => false, 'error' => 'POST required.']);
    exit;
}

if (!Session::isLoggedIn()) {
    http_response_code(401);
    outputJson(['ok' => false, 'error' => 'You are not logged in.']);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    http_response_code(400);
    outputJson(['ok' => false, 'error' => 'Request body required.']);
    exit;
}

try {
    /** @var array<string, mixed>|null $payload */
    $payload = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    http_response_code(400);
    outputJson(['ok' => false, 'error' => 'Invalid JSON.']);
    exit;
}

if (!is_array($payload)) {
    http_response_code(400);
    outputJson(['ok' => false, 'error' => 'Invalid JSON object.']);
    exit;
}

$entity = isset($payload['entity']) ? trim((string) $payload['entity']) : '';
$field = isset($payload['field']) ? trim((string) $payload['field']) : '';
$id = isset($payload['id']) ? (int) $payload['id'] : 0;
$value = isset($payload['value']) ? (string) $payload['value'] : '';

if ($entity === '' || $field === '' || $id <= 0) {
    http_response_code(400);
    outputJson(['ok' => false, 'error' => 'entity, id, and field are required.']);
    exit;
}

try {
    $result = lanlistInlineEditApply($entity, $id, $field, $value);
    outputJson($result);
} catch (LanlistInlineEditException $e) {
    http_response_code($e->httpStatus());
    outputJson(['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    outputJson(['ok' => false, 'error' => 'Inline edit failed.']);
}
