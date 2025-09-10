<?php
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function generateTrxId() {
    return uniqid('TRX-');
}

function getCurrentTime() {
    return date('His');
}

function sendWebhook($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function callProviderAPI($data) {
    $api_url = 'http://213.163.206.110:3333/api';
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
?>