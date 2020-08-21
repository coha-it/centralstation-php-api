<?php

/*
    curl -v -H "Accept: application/json" -H "Content-type: application/json" 
    -X POST -d '{"person":{"first_name":"Marian","name":"Miller"}}'  
    https://accountname.centralstationcrm.net/api/people.json 

    {"person":{"id":1545412,"account_id":21,"user_id":null,"title":null,"gender":2,
    "first_name":"Marian","name":"Miller","background":null,"created_by_user_id":1781,
    "updated_by_user_id":null,
    "created_at":"2015-07-20T13:26:42.190Z","updated_at":"2015-07-20T13:26:42.190Z"}}
*/

class MyCohaCrmApi {

    public function fireApiCall($url, $key, $params, $timestamp)
    {

        // Add Key to Param
        $params = self::getParams($params);
        $params['apikey'] = $key;

        $cURLConnection = curl_init();

        curl_setopt($cURLConnection, CURLOPT_POST, true);
        curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
        curl_setopt($cURLConnection, CURLOPT_URL, $url);
        curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($cURLConnection);
        curl_close($cURLConnection);

        // Log to File
        $this->log('crm-response', $response, $timestamp);

        // Maybe hier response code. wenn ungleich 200 dann log + email an it@ raus!
        // HTTP-Status-Code prüfen
        if (!curl_errno($cURLConnection)) {
            switch ($http_code = curl_getinfo($cURLConnection, CURLINFO_HTTP_CODE)) {
                case 200:
                    // Alles Ok!
                    break;
                default:
                    // echo 'Unerwarter HTTP-Code: ', $http_code, "\n";
                    break;
            }
        }
    }

    public static function getParams($params) {
        $email = self::getParam($params, 'email');
        $phone = self::getParam($params, 'phone');

        return [
            'person' => [
                'title'         => self::getParam($params, 'title'),
                'first_name'    => self::getParam($params, 'first_name'),
                'name'          => self::getParam($params, 'name'),
                'email'         => $email,
                'phone'         => $phone,
                'company'       => self::getParam($params, 'company'),
                'background'    => self::getParam($params, 'background'),

                'positions_attributes' => [
                    [
                        'id' => '',
                        'company_id' => '',
                        'company_name' => self::getParam($params, 'company_name'),
                    ]
                ],

                "tels_attributes" => [
                    [
                        "id" => '',
                        "name" => $phone,
                        // "atype" => "office"
                    ]
                ],

                "emails_attributes" => [
                    [
                        "id" => '',
                        "name" => $email,
                        // "atype" => "office"
                    ]
                ],

                'tags_attributes' => [
                    [
                        'id' => '',
                        'name' => 'Online-Shop Kontaktformular'
                    ],
                    [
                        'id' => '',
                        'name' => self::getParam($params, 'form_tag')
                    ]
                ]
            ]
        ];
    }

    public static function getParam($params, $key) {
        switch ($key) {
            case 'title':
                return $params['anrede'] ?? '';
            
            case 'first_name':
                return $params['vorname'] ?? $params['firstname'] ?? '';
            
            case 'name':
                return $params['nachname'] ?? $params['lastname'] ?? '';

            case 'email':
                return $params['email'] ?? $params['e_mail'] ?? $params['mail'] ?? '';

            case 'phone':
                return $params['phone'] ?? $params['telefon'] ?? $params['mobile'] ?? '';
            
            case 'company':
            case 'company_name':
                return $params['unternehmen'] ?? $params['company'] ?? $params['company_name'];

            case 'background':
                $r = '';

                $b = array_key_exists('betreff', $params);
                $k = array_key_exists('kommentar', $params);

                $r .= $b ? 'Betreff: "'.$params['betreff'].'"' : '';
                $r .= $b && $k ? ".\r\n" : '';
                $r .= $k ? 'Kommentar: "'.$params['kommentar'].'"' : '';

                $r .= ". \r\n\r\n\r\nAlle Formular-Parameter: " . self::getFormDataAsString($params);
                return $r;
            
            case 'form_tag':
                return 'form-id-' . ($params['id'] ?? '');

            default:
                return '';
        }
    }

    public static function getFormDataAsString($params) {
        $cleared = $params;

        // Clear Params
        unset($cleared["__csrf_token"]);
        unset($cleared["_csrf_token"]);
        unset($cleared["csrf_token"]);
        unset($cleared["__csrf"]);
        unset($cleared["_csrf"]);
        unset($cleared["csrf"]);
        unset($cleared["__token"]);
        unset($cleared["_token"]);
        unset($cleared["token"]);

        // Clear from Captchas
        unset($cleared["captchaName"]);
        unset($cleared["captcha_name"]);
        unset($cleared["captcha"]);

        // Clear from Submit
        unset($cleared["Submit"]);
        unset($cleared["submit"]);

        // Clear from Forcemails
        unset($cleared["forceMail"]);
        unset($cleared["force_mail"]);

        // Return Cleared Params
        return json_encode($cleared, JSON_PRETTY_PRINT);
    }

    public static function log($filename = 'default-error-log.log', $content = '', $timestamp = '') {
        $dir = __DIR__;
        $sep = DIRECTORY_SEPARATOR;
        $filepath = $dir.$sep.'logs'.$sep.$filename.'.log';
        $filecontent = "[{$timestamp}]\r\n{$content}\r\n\r\n";
        file_put_contents($filepath, $filecontent, FILE_APPEND);
    }

}
