<?php

require_once "lib/SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", "lib");
$c->register();

use \Tuxed\OAuth\Scope as Scope;
use \Tuxed\OAuth\ScopeException as ScopeException;

class ScopeTest extends PHPUnit_Framework_TestCase {

    function testBasicScope() {
        $s = new Scope("read write delete");
        $this->assertTrue($s->isSubsetOf(new Scope("read write delete update")));
        $this->assertTrue($s->hasScope(new Scope("read")));
        $this->assertTrue($s->hasScope(new Scope("write")));
        $this->assertEquals("delete read write", $s->getScope());
        $this->assertEquals(array("delete", "read", "write"), $s->getScopeAsArray());
    }

    function testBasicScopeArray() {
        $s = new Scope(array("read", "write", "delete"));
        $this->assertEquals("delete read write", $s->getScope());
        $this->assertEquals(array("delete", "read", "write"), $s->getScopeAsArray());
    }

    function testEmptyScope() {
        $s = new Scope(NULL);
        $this->assertEquals("", $s->getScope());
        $this->assertEquals(array(), $s->getScopeAsArray());
        $t = new Scope("");
        $this->assertEquals("", $t->getScope());        
        $this->assertEquals(array(), $t->getScopeAsArray());
        $u = new Scope(array());
        $this->assertEquals("", $u->getScope());        
        $this->assertEquals(array(), $u->getScopeAsArray());
    }

    function testFailingScope() {
        $s = new Scope("read write delete");
        $this->assertFalse($s->isSubsetOf(new Scope("read write update")));
        $this->assertFalse($s->hasScope(new Scope("foo")));
    }

    /**
     * @expectedException \Tuxed\OAuth\ScopeException
     */
    function testMalformedScope() {
        $s = new Scope(" ");
    }

    function testMerge() {
        $s = new Scope("read write delete merge");
        $t = new Scope("write delete");
        $s->mergeWith($t);
        $this->assertEquals("delete merge read write", $s->getScope());
        $this->assertEquals(array("delete", "merge", "read", "write"), $s->getScopeAsArray());
    }

    function testClone() {
        $s = new Scope("read write delete merge");
        $t = new Scope("write delete");
        $c = clone $s;
        $c->mergeWith($t);
        $this->assertEquals("delete merge read write", $c->getScope());
        $this->assertEquals(array("delete", "merge", "read", "write"), $c->getScopeAsArray());
    }

}

?>
