<?php

require __DIR__ . '/vendor/autoload.php';

use Konvertera\Engine\JsonCategoryRepository;

$repo = new JsonCategoryRepository(__DIR__ . '/resources');

echo "--- Length Test ---
";
$category = $repo->getCategory('length');
$unit = $category->getUnit('meter');

echo "Unit: " . ($unit->name['sv'] ?? $unit->key) . "\n";
echo "Summary (SV): " . ($unit->definition['summary']['sv'] ?? 'MISSING') . "\n";
echo "Body (SV): " . ($unit->definition['body']['sv'] ?? 'MISSING') . "\n";
echo "Source: " . ($unit->definition['source'] ?? 'NONE') . "\n";

echo "\n--- Mass Test ---
";
$mass = $repo->getCategory('mass');
$kg = $mass->getUnit('kilogram');
echo "Unit: " . ($kg->name['sv'] ?? $kg->key) . "\n";
echo "Summary (EN): " . ($kg->definition['summary']['en'] ?? 'MISSING') . "\n";
echo "Source: " . ($kg->definition['source'] ?? 'NONE') . "\n";