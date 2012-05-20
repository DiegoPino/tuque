<?php
require_once "FedoraRelationships.php";

class FedoraRelationshipsTest extends PHPUnit_Framework_TestCase {

  function testRelationshipDescription() {
$expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<RDF xmlns="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:fuckyah="http://crazycool.com#">
  <Description rdf:about="info:fedora/one">
    <fuckyah:woot>test</fuckyah:woot>
  </Description>
</RDF>
XML;
    $datastream = new NewFedoraDatastream('RELS-INT', 'M');
    $rel = new FedoraRelationships($datastream);

    $rel->registerNamespace('fuckyah', 'http://crazycool.com#');
    $rel->add('one', 'http://crazycool.com#', 'woot', 'test', TRUE);

    $this->assertXmlStringEqualsXmlString($expected, $datastream->content);

    $relationships = $rel->get('one');
    $this->assertEquals(1, count($relationships));
    $this->assertEquals('fuckyah', $relationships[0]['predicate']['alias']);
    $this->assertEquals('http://crazycool.com#', $relationships[0]['predicate']['namespace']);
    $this->assertEquals('woot', $relationships[0]['predicate']['value']);
    $this->assertTrue($relationships[0]['object']['literal']);
    $this->assertEquals('test', $relationships[0]['object']['value']);
  }

  function testRelationshipLowerD() {
    $content = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<RDF xmlns="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:fuckyah="http://crazycool.com#">
  <description rdf:about="info:fedora/one">
    <fuckyah:woot>test</fuckyah:woot>
  </description>
</RDF>
XML;

    $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<RDF xmlns="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:fuckyah="http://crazycool.com#">
  <description rdf:about="info:fedora/one">
    <fuckyah:woot>test</fuckyah:woot>
    <fuckyah:woot>1234</fuckyah:woot>
  </description>
</RDF>
XML;
    $datastream = new NewFedoraDatastream('RELS-INT', 'M');
    $datastream->content = $content;
    $rel = new FedoraRelationships($datastream);

    $rel->add('one', 'http://crazycool.com#', 'woot', '1234', TRUE);
    $this->assertXmlStringEqualsXmlString($expected, $datastream->content);
  }
}