<?php

declare(strict_types=1);

namespace ComfortPay;

use Omnipay\ComfortPay\Message\CardTransactionResponse;
use Omnipay\ComfortPay\Message\ChargeRequest;
use PHPUnit\Framework\TestCase;
use SoapFault;

class SoapFaultTest extends TestCase
{
    /**
     * @dataProvider SoapFaultDetailDataProvider
     */
    public function testSoapFault($data, $expected)
    {
        $soapClientMock = $this->getMockFromWsdl(__DIR__ . '/../../src/ComfortPay/Teleplatba_1_0.wsdl');
        $soapClientMock->method('doCardTransaction')
            ->willThrowException(new SoapFault("SOAP-ENV:Receiver", '', null, $data));

        $chargeRequest = $this->getMockBuilder(ChargeRequest::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getSoapClient', 'getTestMode'])
        ->getMock();

        $chargeRequest->method('getSoapClient')->willReturn($soapClientMock);
        $chargeRequest->method('getTestMode')->willReturn(false);

        $result = $chargeRequest->sendData([
            'transactionId' => '123456789',
            'referedCardId' => 'refered-card-123',
            'merchantId' => 'merchant-123',
            'terminalId' => 'terminal-123',
            'amount' => 1,
            'parentTransactionId' => 1,
            'cc' => '4405 77XX XXXX XXXX',
            'e2eReference' => '',
            'transactionType' => 'PURCHASE',
        ]);

        $this->assertInstanceOf(CardTransactionResponse::class, $result);
        $this->assertSame('123456789', $result->getTransactionId());
        $this->assertSame($expected, $result->getTransactionStatus());
        $this->assertFalse($result->getTransactionApproval());
    }

    /**
     * @return array[]
     */
    public function SoapFaultDetailDataProvider()
    {
        /*
        <types:ExceptionType>
            <method>doCardTransaction</method>
            <file>ImplFile</file>
            <line>1360</line>
            <errorCode>50001</errorCode>
            <subsystemId>19</subsystemId>
            <subsystemErrorCode>0</subsystemErrorCode>
            <message></message>
        </types:ExceptionType>
        */
        return [
            [
                (object)[
                    'ExceptionType' => (object)
                    [
                        'method' => 'doCardTransaction',
                        'file' => 'ImplFile',
                        'line' => '1360',
                        'errorCode' => '50001',
                        'subsystemId' => '19',
                        'subsystemErrorCode' => '0',
                        'message' => '',
                    ],
                ],
                50001,
            ],
            [(object) ['ExceptionType' => (object) ['errorCode' => '999']], false],
            [(object) ['ExceptionType' => (object) ['errorCode' => '00']], false],
            [(object) ['ExceptionType' => (object) ['errorCode' => '0']], false],
            [(object) ['ExceptionType' => (object) ['errorCode' => '']], false],
            [(object) ['ExceptionType' => (object) ['errorCode' => null]], false],
            [(object) ['TestObject'], false],
            [null, false],
            ['', false],
        ];
    }
}
