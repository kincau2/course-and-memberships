<?php

namespace transloadit\test\system\TransloaditRequest;

class TransloaditRequestAssemblyCreateTest extends \transloadit\test\SystemTestCase {
  public function testRoot() {
    $this->request->setMethodAndPath('POST', '/assemblies');
    $this->request->files[] = TEST_FIXTURE_DIR . '/image-resize-robot.jpg';
    $this->request->params = [
      'steps' => [
        'resize' => [
          'robot' => '/image/resize',
          'width' => 100,
          'height' => 100,
          'result' => true,
        ],
      ],
    ];
    $response = $this->request->execute();

    $this->assertEquals('ASSEMBLY_EXECUTING', $response->data['ok']);
  }
}
