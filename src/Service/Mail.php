<?php


namespace App\Service;

use Mailjet\Client;
use Mailjet\Resources;

class Mail
{

    private $apiKey = 'e641f424f6d0693b5e078d058e868f7d';
    private $apiKeySecret = 'd35f425169e2896971b59c6d579cfcde';

    public function send($toEmail, $toName, $subject, $content)
    {
        $mj       = new Client($this->apiKey, $this->apiKeySecret, true, ['version' => 'v3.1']);
        $body     = [
            'Messages' => [
                [
                    'From'             => [
                        'Email' => "romain.laurent23@gmail.com",
                        'Name'  => "La Boutique Française"
                    ],
                    'To'               => [
                        [
                            'Email' => $toEmail,
                            'Name'  => $toName
                        ]
                    ],
                    'TemplateID'       => 3121424,
                    'TemplateLanguage' => true,
                    'Subject'          => $subject,
                    'Variables'        => [
                        'content' => $content
                    ]
                ]
            ]
        ];
        $response = $mj->post(Resources::$Email, ['body' => $body]);
        return $response->success();

    }
}
