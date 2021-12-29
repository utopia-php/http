<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @author Appwrite Team <team@appwrite.io>
 * @version 1.0 RC4
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Validator;

use PHPUnit\Framework\TestCase;

class ArrayListTest extends TestCase
{
    /**
     * @var ArrayList
     */
    protected $arrayList1 = null;

    /**
     * @var ArrayList
     */
    protected $arrayList2 = null;

    /**
     * @var ArrayList
     */
    protected $arrayList3 = null;

    public function setUp():void
    {
        $this->arrayList1 = new ArrayList(new Text(100));
        $this->arrayList2 = new ArrayList(new Numeric());
        $this->arrayList3 = new ArrayList(new Numeric(), 2);
    }

    public function tearDown():void
    {
        $this->arrayList1 = null;
        $this->arrayList2 = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals(true, $this->arrayList1->isValid([0 => 'string', 1 => 'string']));
        $this->assertEquals(true, $this->arrayList1->isValid(['string', 'string']));
        $this->assertEquals(false, $this->arrayList1->isValid(['string', 'string', 3]));
        $this->assertEquals(false, $this->arrayList1->isValid('string'));
        $this->assertEquals(false, $this->arrayList1->isValid('string'));

        $this->assertEquals(false, $this->arrayList2->isValid('string'));
        $this->assertEquals(true, $this->arrayList2->isValid([1, 2, 3]));
        $this->assertEquals(false, $this->arrayList2->isValid(1, '2', 3));
        $this->assertEquals($this->arrayList1->getType(), \Utopia\Validator::TYPE_STRING);
        $this->assertEquals($this->arrayList2->getType(), \Utopia\Validator::TYPE_MIXED);
        $this->assertEquals($this->arrayList1->isArray(), true);

        $this->assertEquals(true, $this->arrayList3->isValid([1]));
        $this->assertEquals(true, $this->arrayList3->isValid([1, 2]));
        $this->assertEquals(false, $this->arrayList3->isValid([1, 2, 3]));
    }
}
