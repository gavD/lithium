<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template\view\adapter;

use lithium\core\Libraries;
use lithium\template\view\adapter\File;

class FileTest extends \lithium\test\Unit {

	protected $_path;

	public function setUp() {
		$this->_path = Libraries::get(true, 'resources') . '/tmp/tests';

		$template1 = '<' . '?php echo $foo; ?' . '>';
		$template2 = '<' . '?php echo $this["foo"]; ?' . '>';
		file_put_contents("{$this->_path}/template1.html.php", $template1);
		file_put_contents("{$this->_path}/template2.html.php", $template2);
	}

	public function tearDown() {
		unlink("{$this->_path}/template1.html.php");
		unlink("{$this->_path}/template2.html.php");
	}

	public function testRenderingWithExtraction() {
		$file = new File();

		$content = $file->render("{$this->_path}/template1.html.php", array('foo' => 'bar'));
		$this->assertEqual('bar', $content);

		$content = $file->render("{$this->_path}/template2.html.php", array('foo' => 'bar'));
		$this->assertEqual('bar', $content);
	}

	public function testRenderingWithNoExtraction() {
		$backup = error_reporting();
		error_reporting(E_ALL);

		$file = new File(array('extract' => false));
		$path = $this->_path;

		$this->assertException('Undefined variable: foo', function()  use ($file, $path) {
			$file->render("{$path}/template1.html.php", array('foo' => 'bar'));
		});

		$content = $file->render("{$this->_path}/template2.html.php", array('foo' => 'bar'));
		$this->assertEqual('bar', $content);

		error_reporting($backup);
	}

	public function testContextOffsetManipulation() {
		$file = new File();
		$this->assertFalse(isset($file['title']));

		$file['title'] = 'Document Title';
		$this->assertEqual('Document Title', $file['title']);
		$this->assertTrue(isset($file['title']));

		unset($file['title']);
		$this->assertFalse(isset($file['title']));
	}

	/**
	 * @todo Rewrite this test to generate a temporary template in the resources
	 *       directory.
	 */
	public function testTemplateLocating() {
		$path = Libraries::get(true, 'path') . '/views/pages/home.html.php';
		$this->skipIf(!file_exists($path), 'No default app template.');

		$file = new File(array('paths' => array(
			'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
		)));

		$template = $file->template('template', array(
			'controller' => 'pages', 'template' => 'home', 'type' => 'html'
		));
		$pattern = '/template_pages_home\.html_[0-9a-f]+/';
		$this->assertPattern($pattern, $template);

		$file = new File(array('compile' => false, 'paths' => array(
			'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
		)));
		$template = $file->template('template', array(
			'controller' => 'pages', 'template' => 'home', 'type' => 'html'
		));
		$this->assertPattern('/\/views\/pages\/home\.html\.php$/', $template);

		$this->assertException('/Template not found/', function() use ($file) {
			$file->template('template', array(
				'controller' => 'pages', 'template' => 'foo', 'type' => 'html'
			));
		});
	}

	public function testInvalidTemplateType() {
		$file = new File(array('compile' => false, 'paths' => array(
			'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
		)));

		$this->assertException("Invalid template type 'invalid'.", function() use ($file) {
			$file->template('invalid', array('template' => 'foo'));
		});
	}
}

?>