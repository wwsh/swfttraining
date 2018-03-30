<?php

namespace App\Tests\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\MockObject\MockObject;
use Swift_IdGenerator;
use Swift_KeyCache;
use Swift_Mime_ContentEncoder;
use Swift_Mime_SimpleHeaderSet;
use Swift_Mime_SimpleMessage as SwiftSimpleMessage;
use App\Transport\SendGridTransport;
use PHPUnit\Framework\TestCase;

/**
 * This mocks the original SwiftSimpleMessage for test purposes.
 * 1. Emulating original methods using injected test data.
 * 2. Could be simplified into 1 magic method, unless we want to preserve class type hints.
 *
 * @package App\Tests\Transport
 */
class SwiftSimpleMessageMock extends SwiftSimpleMessage
{
    /** @var array */
    private $data;

    /**
     * @param Swift_Mime_SimpleHeaderSet $headers
     * @param Swift_Mime_ContentEncoder $encoder
     * @param Swift_KeyCache $cache
     * @param Swift_IdGenerator $idGenerator
     * @param null $charset
     */
    public function __construct(
        Swift_Mime_SimpleHeaderSet $headers,
        Swift_Mime_ContentEncoder $encoder,
        Swift_KeyCache $cache,
        Swift_IdGenerator $idGenerator,
        $charset = null
    ) {
        // Disable the original constructor
    }

    /**
     * Inits mock virtual data.
     *
     * @param $data
     */
    public function initializeMock($data)
    {
        $this->data = $data;
    }

    // Following methods override the default ones to return fabricated data.

    public function getSubject()
    {
        return $this->data['subject'];
    }

    public function getFrom()
    {
        return $this->data['from'];
    }

    public function getReplyTo()
    {
        return $this->data['replyTo'];
    }

    public function getTo()
    {
        return $this->data['to'];
    }

    public function getCc()
    {
        return $this->data['cc'];
    }

    public function getBcc()
    {
        return $this->data['bcc'];
    }

    public function getBody()
    {
        return $this->data['body'];
    }
}

class SendGridTransportTest extends TestCase
{
    /** @var SwiftSimpleMessageMock */
    protected $message;

    /** @var Client|MockObject */
    protected $httpConnector;

    /** @var SendGridTransport */
    protected $testable;

    protected function setUp()
    {
        $this->message = $this->getSwiftMessageMock();

        $this->httpConnector = $this->createMock(Client::class);

        $this->testable = new SendGridTransport($this->httpConnector);
    }

    /**
     * @dataProvider inputData
     * @param $swiftMessageData
     * @param $expectedRequestBody
     * @param $expectedCount
     */
    public function testSend($swiftMessageData, $expectedRequestBody, $expectedCount)
    {
        $this->message->initializeMock($swiftMessageData);

        $this->httpConnector->expects($this->once())
            ->method('request')
            ->with('POST', '/mail/send', [RequestOptions::JSON => $expectedRequestBody]);

        $result = $this->testable->send($this->message);

        $this->assertEquals($expectedCount, $result);
    }

    /**
     * Data Provider.
     */
    public function inputData()
    {
        return [
            [
                'swiftMessage' => [
                    'to' => [
                        'test1@mail.com' => 'Test One',
                        'test2@mail.com' => 'Test Two'
                    ],
                    'cc' => null,
                    'bcc' => null,
                    'subject' => 'Hello, World!',
                    'replyTo' => 'test1@mail.com',
                    'from' => [
                        'sender@mail.com' => 'Some sender'
                    ],
                    'body' => '<html><b>Hello, world!</b></html>'
                ],
                'expectedRequestBody' => [
                    'personalizations' => [
                        [
                            'to' => [
                                [
                                    'email' => 'test1@mail.com',
                                    'name' => 'Test One'
                                ],
                                [
                                    'email' => 'test2@mail.com',
                                    'name' => 'Test Two'
                                ]
                            ],
                            'subject' => 'Hello, World!'
                        ]
                    ],
                    'from' => [
                        'email' => 'sender@mail.com',
                        'name' => 'Some sender'
                    ],
                    'reply_to' => [
                        'email' => 'test1@mail.com'
                    ],
                    'subject' => 'Hello, World!',
                    'content' => [
                        [
                            'type' => 'text/html',
                            'value' => '<html><b>Hello, world!</b></html>'
                        ]
                    ]
                ],
                'expectedCount' => 2
            ],
            [
                'swiftMessage' => [
                    'to' => [
                        'test1@mail.com' => 'Test One',
                    ],
                    'cc' => [
                        'test2@mail.com' => 'Test Two'
                    ],
                    'bcc' => [
                        'test3@mail.com' => 'Test Three'
                    ],
                    'subject' => 'Hello, World!',
                    'replyTo' => 'test1@mail.com',
                    'from' => [
                        'sender@mail.com' => 'Some sender'
                    ],
                    'body' => '<html><b>Hello, world!</b></html>'
                ],
                'expectedRequestBody' => [
                    'personalizations' => [
                        [
                            'to' => [
                                [
                                    'email' => 'test1@mail.com',
                                    'name' => 'Test One'
                                ],
                            ],
                            'subject' => 'Hello, World!'
                        ],
                        [
                            'cc' => [
                                [
                                    'email' => 'test2@mail.com',
                                    'name' => 'Test Two'
                                ]
                            ],
                            'subject' => 'Hello, World!'
                        ],
                        [
                            'bcc' => [
                                [
                                    'email' => 'test3@mail.com',
                                    'name' => 'Test Three'
                                ]
                            ],
                            'subject' => 'Hello, World!'
                        ]
                    ],
                    'from' => [
                        'email' => 'sender@mail.com',
                        'name' => 'Some sender'
                    ],
                    'reply_to' => [
                        'email' => 'test1@mail.com'
                    ],
                    'subject' => 'Hello, World!',
                    'content' => [
                        [
                            'type' => 'text/html',
                            'value' => '<html><b>Hello, world!</b></html>'
                        ]
                    ]
                ],
                'expectedCount' => 3
            ]
        ];
    }

    /**
     * @return SwiftSimpleMessageMock
     * @throws \ReflectionException
     */
    protected function getSwiftMessageMock()
    {
        $mock = new SwiftSimpleMessageMock(
            $this->createMock(Swift_Mime_SimpleHeaderSet::class),
            $this->createMock(Swift_Mime_ContentEncoder::class),
            $this->createMock(Swift_KeyCache::class),
            $this->createMock(Swift_IdGenerator::class)
        );

        return $mock;
    }
}
