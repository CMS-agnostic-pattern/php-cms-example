<?php

use Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables from .env file.
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Check if model exists in session
session_start();
if (isset($_SESSION['model'])) {
    $model = $_SESSION['model'];
} else {
    $model = load_model();
    $_SESSION['model'] = $model;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
echo '<h1>Basic CMS prototype</h1>';
switch ($action) {
    case 'add':
    case 'edit':
        // Output the form for adding a new object.
        // there should be type of the object in $_GET['action'].
        if (isset($_GET['type'])) {
            $type = $model['types'][$_GET['type']];
            $fields = $model['fields'];
            $data = [];
            if ($action === 'edit') {
                // Load the object data from the file.
                $contentFolder = $_ENV['CONTENT_FOLDER'] . $_GET['type'] . '/';
                $contentFile = $contentFolder . $_GET['id'] . '.' . $_ENV['CONTENT_FORMAT'];
                $data = json_decode(file_get_contents($contentFile), true);
                echo '<h2>Edit object <em>' . $_GET['id'] . '</em> type of <em>' . $type['name'] . '</em></h2>';
            }
            else {
                echo '<h2>Add a New ' . $type['name'] . '</h2>';
            }
            echo '<form method="post" action="index.php?action=save">';
            echo '<input type="hidden" name="type" value="' . $type['id'] . '">';
            foreach ($type['fields'] as $field) {
                $value = isset($data[$field['id']]) ? $data[$field['id']] : '';
                echo '<div><label for="' . $field['id'] . '"><strong>' . $field['name'] . ':</strong></label><br />';
                switch($fields[$field['type']]['element']) {
                    case 'select':
                        echo 'TBD: select';
                        break;
                    case 'radio':
                        echo 'TBD: radio';
                        break;
                    case 'checkbox':
                        echo '<input type="checkbox" name="' . $field['id'] . '" id="' . $field['id'];
                        if ($value == 'on') {
                            echo '" checked></div>';
                        }
                        else {
                            echo '"></div>';
                        }
                        break;
                    case 'textarea':
                        echo '<textarea name="' . $field['id'] . '" id="' . $field['id'] . '">' .
                            $value . '</textarea></div>';
                        break;
                    default:
                        echo '<input type="text" name="' . $field['id'] . '" id="' . $field['id'] .
                            '" value="' . $value . '"></div>';
                }
            }

            echo '<div><input type="submit" value="Save"></div>';
            echo '</form>';
            echo '<div><a href="index.php">Cancel</a></div>';
        }
        break;
    case 'save':
        // Save the object to the model.
        if (isset($_POST['type'])) {
            $type = $model['types'][$_POST['type']];
            $data = [];
            foreach ($type['fields'] as $field) {
                if (isset($_POST[$field['id']])) {
                    $data[$field['id']] = $_POST[$field['id']];
                }
            }
            $contentFolder = $_ENV['CONTENT_FOLDER'] . $_POST['type'] . '/';
            if (!is_dir($contentFolder)) {
                mkdir($contentFolder, 0777, true);
            }
            $contentFile = $contentFolder . $_POST['id'] . '.' . $_ENV['CONTENT_FORMAT'];
            switch ($_ENV['CONTENT_FORMAT']) {
                // TBD: add more formats.
                case 'json':
                default:
                    file_put_contents($contentFile, json_encode($data));
            }

            echo '<div><em>Object saved successfully!</em></div>';
            echo '<div><a href="index.php">Back to the Dashboard</a></div>';
        } else {
            echo 'Error: Invalid object type!';
        }
        break;
    case 'delete':
        // Output conformation form for deleting the object.
        echo '<h2>Delete object <em>' . $_GET['id'] . '</em> type of <em>' . $_GET['type'] . '</em>?</h2>';
        echo '<form method="post" action="index.php?action=delete_confirm&type=' . $_GET['type'] . '&id=' . $_GET['id'] . '">';
        echo '<div><input type="submit" value="Delete"></div>';
        echo '</form>';
        echo '<div><a href="index.php">Cancel</a></div>';
        break;
    case 'delete_confirm':
        // Delete the object from content folder.
        $contentFolder = $_ENV['CONTENT_FOLDER'] . $_GET['type'] . '/';
        $contentFile = $contentFolder . $_GET['id'] . '.' . $_ENV['CONTENT_FORMAT'];
        if (file_exists($contentFile)) {
            unlink($contentFile);
            echo '<div><em>Object deleted successfully!</em></div>';
        } else {
            echo '<div><em>Error: Object not found!</em></div>';
        }
        echo '<div><a href="index.php">Back to the Dashboard</a></div>';
        break;
    case 'model':
        echo '<div><a href="index.php">Back to the Dashboard</a></div>';
        echo '<pre>';
        print_r($model);
        echo '</pre>';
        break;
    case 'list':
    default:
        echo '<h2>Add a New Object</h2>';
        echo '<ul>';
        foreach ($model['types'] as $type) {
            if (isset($type['status']) && $type['status'] === 'active') {
                echo '<li><a href="index.php?action=add&type=' . $type['id'] . '">' . $type['name'] . '</a></li>';
            }
        }
        echo '</ul>';
        // Output list of available objects by types.
        echo '<h2>List of Available Objects</h2>';
        $objects = []; // Object list array.
        foreach ($model['types'] as $type) {
            if (isset($type['status']) && $type['status'] === 'active') {
                echo '<h3>' . $type['name'] . '</h3>';
                $contentFolder = $_ENV['CONTENT_FOLDER'] . $type['id'] . '/';
                if (is_dir($contentFolder)) {
                    $files = scandir($contentFolder);
                    foreach ($files as $file) {
                        if ($file !== '.' && $file !== '..') {
                            $contentFile = $contentFolder . $file;
                            $data = json_decode(file_get_contents($contentFile), true);
                            if (isset($data['id']) && $type['id']) {
                                $objects[$type['id']][$data['id']] = $type['id'] . '/' . $data['id'] . '.' . $_ENV['CONTENT_FORMAT'];
                                echo '<div>';
                                echo '<span><a href="index.php?action=edit&type=' . $type['id'] .
                                    '&id=' . $data['id'] . '">Edit</a> | <a href="index.php?action=delete&type=' .
                                    $type['id'] . '&id=' . $data['id'] . '">Delete</a></span>';
                                foreach ($type['fields'] as $field) {
                                    if (in_array($field['id'], ['id', 'title', 'slug', 'published']) && isset($data[$field['id']])) {
                                        echo '&nbsp;&nbsp;<span><strong>' . $field['name'] . ':</strong> ' . $data[$field['id']] . '</span>';
                                    }
                                }
                                echo '</div>';
                            }
                        }
                    }
                }
            }
        }
        // Current data model.
        echo '<h2>Current data model</h2>';
        echo '<a href="index.php?action=model">Compiled version</a>';
        // Update the object index file.
        // TBD: To find the rights place to do that.
        file_put_contents($_ENV['CONTENT_FOLDER'] . 'index.json', json_encode($objects));
        break;
}

exit;

// Load model function.
function load_model() {
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
        // Add parent fields to the children types.
        $modified_types = $model['types'];
        foreach ($model['types'] as $key => $type) {
            if (!isset($type['parent'])) {
                $parent_id = $key;
                unset($modified_types[$key]);
                foreach ($modified_types as $midified_key => $modified_type) {
                    if (isset($modified_type['parent']) && $modified_type['parent'] === $parent_id) {
                        // Add parent fields to the children types in the start of the array.
                        $parent_fields = [];
                        foreach ($type['fields'] as $field) {
                            if (isset($model['types'][$midified_key]['fields'][$field['id']])) {
                                $model['types'][$midified_key]['fields'][$field['id']] = $field;
                            }
                            else {
                                $parent_fields[$field['id']] = $field;
                            }
                        }
                        $model['types'][$midified_key]['fields'] = array_merge($parent_fields, $model['types'][$midified_key]['fields']);
                        unset($model['types'][$key]['parent']);
                    }
                }
            }
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
