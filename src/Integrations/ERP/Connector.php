<?php

namespace Src\Integrations\ERP;

class Connector
{
    public $result;

    public function __construct(string $action, string $method = 'POST', array $requestData = [])
    {
        if ($method !== 'GET' && $method !== 'POST') {
            throw new \Exception('Undefined method');
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.sushinook.de/api/v1/integration/' . $action,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json',
                'x-token: CRLauZnNJDpgaACyAklH0Aq6eVTIizwYScV2Sccm',
            ),
        ]);

        if ($method === 'POST') {
            curl_setopt_array($curl, [
                CURLOPT_POSTFIELDS => $requestData ? json_encode($requestData) : ''
            ]);
        }

        $result = json_decode(curl_exec($curl));
        curl_close($curl);

        return $this->result = $result;
    }
}