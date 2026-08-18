<?php //18-08-2026

$mensaje = 'Test message';

// WhatsApp sending via Twilio ------------------------------------------------------

$twilioHabilitado = true;
$errorWA = isset($errorWA) ? (string)$errorWA : '';
$mensaje = isset($mensaje) ? trim((string)$mensaje) : '';

$reporteWA = array(
    'enabled' => $twilioHabilitado,
    'message_empty' => ($mensaje === ''),
    'message_length' => strlen($mensaje),
    'recipients' => array(),
    'send_attempts' => 0,
    'success_count' => 0,
    'error_count' => 0,
    'status' => 'NOT_STARTED',
    'timestamp' => date('Y-m-d H:i:s')
);

$telefonos = array(
    'whatsapp:+584143459825',
    // 'whatsapp:+584127432683' // Gustavo Valero
);

if ($mensaje !== '' && $twilioHabilitado) {
    $accountSid = 'xxxxxxx';
    $authToken  = 'yyyyyyy';
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";

    foreach ($telefonos as $numeroDestino) {
        $reporteWA['send_attempts']++;

        $data = array(
            'From' => 'whatsapp:+14155238886',
            'To'   => $numeroDestino,
            'Body' => $mensaje
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => "{$accountSid}:{$authToken}",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 20
        ));

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $status = 'ERROR';
        $details = 'No response received';
        $messageSid = 'N/A';

        if ($response === false || $curlError !== '') {
            $details = $curlError;
            $errorWA .= 'Twilio failed for ' . $numeroDestino . ': ' . $curlError . '; ';
            $reporteWA['error_count']++;
        } elseif ($httpCode < 200 || $httpCode >= 300) {
            $details = trim((string)$response);
            $errorWA .= 'Twilio ' . $numeroDestino . ' returned HTTP ' . $httpCode . ': ' . $details . '; ';
            $reporteWA['error_count']++;
        } else {
            $status = 'SUCCESS';
            $decodedResponse = json_decode((string)$response, true);
            $messageSid = is_array($decodedResponse) && isset($decodedResponse['sid']) ? $decodedResponse['sid'] : 'N/A';
            $details = 'Message sent successfully';
            $reporteWA['success_count']++;
        }

        $reporteWA['recipients'][] = array(
            'to' => $numeroDestino,
            'status' => $status,
            'http_code' => $httpCode,
            'message_sid' => $messageSid,
            'details' => $details,
            'response' => is_string($response) ? trim($response) : ''
        );
    }

    $reporteWA['status'] = ($reporteWA['error_count'] === 0) ? 'SUCCESS' : 'PARTIAL_ERROR';
} else {
    $reporteWA['status'] = ($mensaje === '') ? 'SKIPPED_EMPTY_MESSAGE' : 'DISABLED';
    if ($mensaje === '') {
        $errorWA .= 'Twilio skipped because the message is empty; ';
    } else {
        $errorWA .= 'Twilio is disabled; ';
    }
}

echo '<div style="font-family:Arial,Helvetica,sans-serif;max-width:900px;margin:16px auto;padding:20px;border:1px solid #d8d8d8;border-radius:10px;background:#f9f9f9;color:#1f1f1f;box-shadow:0 2px 8px rgba(0,0,0,0.04);">';
echo '<h2 style="margin:0 0 12px 0;color:#111;">Twilio WhatsApp Delivery Report</h2>';
echo '<p style="margin:0 0 12px 0;color:#555; font-size:13px;"><strong>Timestamp:</strong> ' . htmlspecialchars($reporteWA['timestamp'], ENT_QUOTES, 'UTF-8') . '</p>';
echo '<ul style="margin:0 0 16px 20px; padding:0; line-height:1.8;">';
echo '<li><strong>Enabled:</strong> ' . ($reporteWA['enabled'] ? 'Yes' : 'No') . '</li>';
echo '<li><strong>Status:</strong> ' . htmlspecialchars($reporteWA['status'], ENT_QUOTES, 'UTF-8') . '</li>';
echo '<li><strong>Message empty:</strong> ' . ($reporteWA['message_empty'] ? 'Yes' : 'No') . '</li>';
echo '<li><strong>Message length:</strong> ' . $reporteWA['message_length'] . ' characters</li>';
echo '<li><strong>Recipients:</strong> ' . count($telefonos) . '</li>';
echo '<li><strong>Send attempts:</strong> ' . $reporteWA['send_attempts'] . '</li>';
echo '<li><strong>Successful:</strong> ' . $reporteWA['success_count'] . '</li>';
echo '<li><strong>Errors:</strong> ' . $reporteWA['error_count'] . '</li>';
echo '</ul>';

echo '<div style="margin-top:12px;">';
if (!empty($reporteWA['recipients'])) {
    echo '<h3 style="margin:0 0 8px 0; color:#222;">Recipient details</h3>';
    echo '<div style="overflow-x:auto; margin-bottom:16px;">';
    echo '<table style="width:100%; border-collapse:collapse; background:#fff; border:1px solid #d8d8d8; border-radius:8px; overflow:hidden; font-size:13px;">';
    echo '<thead>';
    echo '<tr style="background:#eef2f7;">';
    echo '<th style="text-align:left; padding:10px 12px; border-bottom:1px solid #d8d8d8;">To</th>';
    echo '<th style="text-align:left; padding:10px 12px; border-bottom:1px solid #d8d8d8;">Status</th>';
    echo '<th style="text-align:left; padding:10px 12px; border-bottom:1px solid #d8d8d8;">HTTP</th>';
    echo '<th style="text-align:left; padding:10px 12px; border-bottom:1px solid #d8d8d8;">Message SID</th>';
    echo '<th style="text-align:left; padding:10px 12px; border-bottom:1px solid #d8d8d8;">Details</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($reporteWA['recipients'] as $recipient) {
        echo '<tr>';
        echo '<td style="padding:10px 12px; border-bottom:1px solid #eeeeee; vertical-align:top;">' . htmlspecialchars($recipient['to'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td style="padding:10px 12px; border-bottom:1px solid #eeeeee; vertical-align:top;">' . htmlspecialchars($recipient['status'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td style="padding:10px 12px; border-bottom:1px solid #eeeeee; vertical-align:top;">' . (int)$recipient['http_code'] . '</td>';
        echo '<td style="padding:10px 12px; border-bottom:1px solid #eeeeee; vertical-align:top;">' . htmlspecialchars($recipient['message_sid'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td style="padding:10px 12px; border-bottom:1px solid #eeeeee; vertical-align:top;">' . htmlspecialchars($recipient['details'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    foreach ($reporteWA['recipients'] as $recipient) {
        if (!empty($recipient['response'])) {
            $decodedJson = json_decode($recipient['response'], true);
            echo '<div style="margin:0 0 16px 0;">';
            echo '<h4 style="margin:0 0 8px 0; color:#222;">Response</h4>';
            echo '<div style="background:#f9f9f9; border:1px solid #d8d8d8; border-radius:8px; padding:12px; font-size:12px; line-height:1.7; overflow-x:auto;">';
            if (is_array($decodedJson)) {
                $fieldLabels = array(
                    'account_sid' => 'Account SID',
                    'api_version' => 'API version',
                    'body' => 'Body',
                    'date_created' => 'Date created',
                    'date_sent' => 'Date sent',
                    'date_updated' => 'Date updated',
                    'direction' => 'Direction',
                    'error_code' => 'Error code',
                    'error_message' => 'Error message',
                    'from' => 'From',
                    'messaging_service_sid' => 'Messaging service SID',
                    'num_media' => 'Media count',
                    'num_segments' => 'Segments',
                    'price' => 'Price',
                    'price_unit' => 'Price unit',
                    'sid' => 'Message SID',
                    'status' => 'Status',
                    'subresource_uris' => 'Subresource URIs',
                    'to' => 'To',
                    'uri' => 'URI'
                );

                echo '<div style="display:grid; grid-template-columns: minmax(180px, 220px) 1fr; gap:8px 12px;">';
                foreach ($decodedJson as $key => $value) {
                    $label = isset($fieldLabels[$key]) ? $fieldLabels[$key] : ucwords(str_replace(array('_', '-'), ' ', $key));
                    $formattedValue = is_array($value)
                        ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                        : ((string)$value === '' ? '—' : (string)$value);

                    echo '<div style="font-weight:700; padding-right:8px;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ':</div>';
                    echo '<div style="word-break:break-word;">' . htmlspecialchars($formattedValue, ENT_QUOTES, 'UTF-8') . '</div>';
                }
                echo '</div>';
            } else {
                echo '<pre style="margin:0; white-space:pre-wrap; word-break:break-word;">' . htmlspecialchars($recipient['response'], ENT_QUOTES, 'UTF-8') . '</pre>';
            }
            echo '</div>';
            echo '</div>';
        }
    }
}

echo '<h3 style="margin:0 0 8px 0; color:#222;">Error summary</h3>';
if ($errorWA !== '') {
    echo '<p style="margin:0; color:#b00020; background:#fff1f1; border:1px solid #f0c4c4; border-radius:6px; padding:10px;">' . htmlspecialchars(trim($errorWA), ENT_QUOTES, 'UTF-8') . '</p>';
} else {
    echo '<p style="margin:0; color:#0a7d2d; background:#eefaf1; border:1px solid #cfead9; border-radius:6px; padding:10px;">No errors detected.</p>';
}

echo '</div>';
echo '</div>';
// -------------------------------------------------------------------------------
