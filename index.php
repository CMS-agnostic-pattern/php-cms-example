<?php

use Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';

$model = load_model();

echo '<pre>';
print_r($model);
echo '</pre>';

exit;

// Load model function.
function load_model() {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
    if (isset($_ENV['MODEL_ROOT_FOLDER']) &&
        isset($_ENV['MODEL_ROOT_FILE'])) {
        $root_path = $_ENV['MODEL_ROOT_FOLDER'];
        $root_file = $_ENV['MODEL_ROOT_FILE'];
        $model = json_decode(file_get_contents($root_path . $root_file), true);
        foreach ($model['types'] as $key => $type) {
            if (is_string($type)) {
                // need to load the sub-model from the path $root_path . $type
                // and replace the string with the sub-model
                $subModelPath = $root_path . $type;
                $subModel = json_decode(file_get_contents($subModelPath), true);
                unset($model['types'][$key]);
                $model['types'][$subModel['id']] = $subModel;
            }
        }
        return $model;
    }
    return FALSE;
}