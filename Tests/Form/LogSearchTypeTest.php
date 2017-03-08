<?php

namespace Lexik\Bundle\MonologBrowserBundle\Tests\Form;

use Lexik\Bundle\MonologBrowserBundle\Form\LogSearchType;
use Lexik\Bundle\MonologBrowserBundle\Model\LogRepository;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Definition of LogSearchTypeTest
 *
 * @author Diego de Biagi <diegobiagiviana@gmail.com>
 */
class LogSearchTypeTest extends TypeTestCase {

    /** @var  LogRepository */
    private $logRepository;

    protected function setUp() {
        $mock = $this->getMockBuilder(LogRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('getLogsLevel')
            ->will($this->returnValue([
                100 => 'Debug',
                200 => 'Info',
                300 => 'Warning',
                400 => 'Error',
                500 => 'Critical'
            ]));

        $this->logRepository = $mock;

        parent::setUp();
    }

    protected function getExtensions() {
        $type = new LogSearchType($this->logRepository);

        return [
            new PreloadedExtension([$type], [])
        ];
    }

    public function testSubmit() {
        $data = [
            'date_from' => new \DateTime(),
            'time_from' => 1800,
            'date_to' => new \DateTime(),
            'time_to' => 1800,
            'term' => 'Random term',
            'level' => 100
        ];

        $form = $this->factory->create(LogSearchType::class, $data);
        $form->submit($data);

        $this->assertTrue($form->isSynchronized(), 'Form should be syncronized.');
        $this->assertTrue($form->isValid(), 'Form should be valid.');
        $this->assertEquals($data, $form->getData(), 'Submited data should be equals to form data.');
    }
}