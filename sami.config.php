<?php

use Sami\Sami;
use Sami\Version\GitVersionCollection;
use Symfony\Component\Finder\Finder;
use Sami\Parser\Filter\TrueFilter;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('Test')
    ->in($dir = __DIR__.'/src')
;

$versions = GitVersionCollection::create($dir)
    ->addFromTags('v0.1.3')
    ->add('master', 'Current Release')
;

$sami = new Sami($iterator, array(
    'versions'             => $versions,
    'title'                => 'CFX Persistence Library',
    'build_dir'            => __DIR__.'/docs/build/%version%',
    'cache_dir'            => __DIR__.'/docs/cache/%version%',
    'default_opened_level' => 2,
));

$sami['filter'] = function () {
        return new TrueFilter();
};

return $sami;


