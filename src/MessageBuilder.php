<?php

namespace Pasya\OneSender;

class MessageBuilder 
{
    private $apiKey;
    private $apiUrl;

    private $messageType;
    private $to;
    private $recipientType;
    private $headerType;
    private $headerValue;
    private $bodyValue;
    private $footerValue;
    private $buttons = [];
    private $templateDevButtons = [];
    private $sections = [];
    private $sectionButtonLabel;
    private $messages = [];
    private $senderClient = false;

    private $errors;

    public function __construct(array $args) {
        $this->apiKey = $args['api_key'] ?? '';
        $this->apiUrl = $args['api_url'] ?? '';

        $this->messageType = 'text';
    }

    public function type(string $type) {
        $this->messageType = $type;

        return $this;
    }
    
    public function to(?string $param) {
        $param = is_array($param) ? 
            implode(',', $this->filterPhones($param)) : 
            self::filterPhone($param);

        $this->to = $param;

        $recipientType = $this->strContains($param, '@g.us') ? 'group' : 'individual';
        $this->recipientType = $this->strContains($recipientType, ',') ? 'individual' : $recipientType;

        return $this;
    }

    public function header(string $param) {
        $this->headerType = !filter_var($param, FILTER_VALIDATE_URL) === false ? 'image' : 'text';
        $this->headerValue = $param;

        return $this;
    }

    public function attachmentUrl(string $param) {
        $this->headerType = 'link';
        $this->attachmentUrl = $param;

        return $this;
    }

    public function content(string $param) {
        $this->bodyValue = $param;

        return $this;
    }

    public function footer(string $param) {
        $this->footerValue = $param;

        return $this;
    }

    public function save() {
        $message = $this->buildMessageData();
        $this->resetMessage();

        return $message;
    }

    public function resetMessage() {
        $this->messageType = 'text';
        $this->to = null;
        $this->recipientType = 'individual';
        $this->headerType = null;
        $this->headerValue = null;
        $this->bodyValue = null;
        $this->footerValue = null;
        $this->buttons = [];
        $this->templateDevButtons = [];
        $this->sections = [];
        $this->sectionButtonLabel = null;
    }

    public function send($messageList = []) {

        if (count($messageList) > 0) {
            $this->messages = $messageList;
        }

        if (count($this->messages) == 0) {
            $this->messages[] = $this->buildMessageData();
            $this->resetMessage();
        }

        if (!$this->senderClient) {
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ];

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL             => $this->apiUrl,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_ENCODING        => '',
                CURLOPT_MAXREDIRS       => 10,
                CURLOPT_TIMEOUT         => 0,
                CURLOPT_FOLLOWLOCATION  => true,
                CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST   => 'POST',
                CURLOPT_POSTFIELDS      => json_encode($this->messages),
                CURLOPT_HTTPHEADER      => $headers,
            ));
    
            $response = curl_exec($curl);
            curl_close($curl);

            if (!$response) {
                return [false, ['error' => 'Failed to send message']];
            }

            $response = json_decode($response, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                return [false, ['error' => 'Failed to decode response']];
            }

            return [$response, null];
        }

        return $this->senderClient->send(
            $this->messages
        );

    }


    public function addButtonLink($label, $url) {
        if ($this->messageType == 'interactive_dev') {
            $this->templateDevButtons[] = [

                'type'      => 'link',
                'parameter' => [
                    'title' => $label,
                    'value' => $url,
                ],
            ];
        }
        
        return $this;
    }

    
    public function addButtonCall($label, $url) {
        if ($this->messageType == 'interactive_dev') {
            $this->templateDevButtons[] = [
                'type'      => 'call',
                'parameter' => [
                    'title' => $label,
                    'value'  => $url,
                ],
            ];
        }
        
        return $this;
    }



    private function buildTextMessage() {
        return [
            'text' => [
                'body' => $this->bodyValue,
            ]
        ];
    }
    
    private function buildImageMessage() {
        return [
            'image' => [
                'link' => $this->attachmentUrl,
                'caption' => $this->bodyValue,
            ]
        ];
    }

    private function buildDocMessage() {
        return [
            'document' => [
                'link' => $this->attachmentUrl,
            ]
        ];
    }

    private function buildTemplateDevMessage() {
        $output = [
            'header' => [
                'type' => $this->headerType,
                'parameter' => ['value' => $this->headerValue],
            ],
            'body' => [
                'type' => 'text',
                'parameter' => ['value' => $this->bodyValue],
            ],
        ];

        if (!empty($this->footerValue)) {
            $output = array_merge($output, [
                'footer' => [
                    'type' => 'text',
                    'parameter' => ['value' => $this->footerValue],
                ],
            ]);
        }

        if (count($this->templateDevButtons) > 0) {
            $output = array_merge($output, [
                'action' => ['buttons' => $this->templateDevButtons],
            ]);
        }

        return ['interactive_dev' => $output];
    }


    private function buildMessageData() {

        switch ($this->messageType) {
            case 'image':
                $message = $this->buildImageMessage();
                break;
            
            case 'document':
                $message = $this->buildDocMessage();
                break;
                
            case 'interactive_dev':
                $message = $this->buildTemplateDevMessage();
                break;
                
            default:
                $message = $this->buildTextMessage();
                break;
        }

        $fields = [
            'type' => $this->messageType,
            'to' => $this->to,
            'recipient_type' => $this->recipientType,
        ];

        
        return array_merge($fields, $message);
    }
   
    private function strContains($string, $search) {
        return strpos($string, $search) !== false;
    }

    public static function filterPhone(string $phone) {
	    $phoneStr = preg_replace('/[^0-9]+/', '', $phone);
	    if (substr($phoneStr, 0, 2) == '08') {
	        $phoneStr = '628' . substr($phoneStr, 2);
	    }
        
        if (strpos($phone, '@g.us') == false) {
            $phoneStr .= '@g.us';
        }

	    return $phone;
	}
    
    private function filterPhones(array $phones) {
        return array_walk_recursive($phones, function(&$v, $k) { 
                $v = self::filterPhone($v); 
            }
        );
	}

}
