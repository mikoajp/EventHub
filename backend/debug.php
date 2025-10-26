<?php
require __DIR__.'/vendor/autoload.php';
$kernel = new App\Kernel('test', true);
$kernel->boot();
$container = $kernel->getContainer();
if (!$container->has('doctrine')) { echo "no doctrine\n"; exit(0);} 
$em = $container->get('doctrine')->getManager();
$metadata = $em->getMetadataFactory()->getAllMetadata();
echo 'meta count='.count($metadata)."\n";
foreach ($metadata as $m) { echo $m->getName()."\n"; }
$tool = new Doctrine\ORM\Tools\SchemaTool($em);
$tool->dropDatabase();
$tool->createSchema($metadata);
echo "schema done\n";
