<?php
ini_set('memory_limit', '2048M');

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

        // Load types from files if they are strings.
        foreach ($model['types'] as $key => $type) {
            if (is_string($type)) {
                // need to load the sub-model from the path $root_path . $type
                // and replace the string with the sub-model
                $subModelPath = $root_path . $type;
                $subModel = json_decode(file_get_contents($subModelPath), true);
                unset($model['types'][$key]);
                $model['types'][$subModel['id']] = $subModel;
            }
            else {
                $model['types'][$type['id']] = $model['types'][$key];
                unset($model['types'][$key]);
            }
        }
        // Field sorting
        foreach ($model['types'] as $key => $type) {
            foreach ($type['fields'] as $weight => $field) {
                $field['weight'] = $weight;
                $model['types'][$key]['fields'][$field['id']] = $field;
                unset($model['types'][$key]['fields'][$weight]);
            }
        }
        foreach ($model['types'] as $key => $type) {
            $model['types'][$key] = load_parent_properties($model['types'], $type);
        }

        // Load fields from files if they are strings.
        foreach ($model['fields'] as $key => $field) {
            if (is_string($field)) {
                // need to load the sub-model from the path $root_path . $type
                // and replace the string with the sub-model
                $subModelPath = $root_path . $field['type'];
                $subModel = json_decode(file_get_contents($subModelPath), true);
                unset($model['fields'][$key]);
                $model['fields'][$field['id']] = $subModel;
            }
            else {
                $model['fields'][$field['id']] = $model['fields'][$key];
                unset($model['fields'][$key]);
            }
        }
        /* TBD: need to load parent properties for fields
        foreach ($model['fields'] as $key => $field) {
            $model['fields'][$key] = load_parent_properties($model['fields'], $field);
        }
        */

        return $model;
    }
    return FALSE;
}

// Load parent properties.
function load_parent_properties(array $initial_data_set, array $data_piece) {
    $result = [];
    if(isset($data_piece['parent'])) {
        $parent = $initial_data_set[$data_piece['id']];
        if (isset($parent['parent'])) {
            $completed_parent = load_parent_properties($initial_data_set, $parent);
        }
        else {
            $completed_parent = $parent;
        }
        foreach ($data_piece as $key => $value) {
            if ($key !== 'parent') {
                $completed_parent[$key] = $value;
            }
            if ($key === 'fields') {
                foreach ($value as $field_key => $field) {
                    $completed_parent['fields'][$field_key] = $field;
                }
            }
        }
        $result = $completed_parent;
    }
    else {
        $result = $data_piece;
    }
    return $result;
}