<?php

use Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';

session_start();

if (!isset($_SESSION['project'])) {
    // Load environment variables from .env file.
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
    $project['models'][0] = [
        'folder' => $_ENV['MODEL_ROOT_FOLDER'],
        'path' => $_ENV['MODEL_ROOT_FILE'],
    ];
    $project['content'] = [
        'folder' => $_ENV['CONTENT_FOLDER'],
        'format' => $_ENV['CONTENT_FORMAT'],
    ];
    $_SESSION['project'] = $project;
}
else {
    $project = $_SESSION['project'];
}

// Check if model exists in session
if (isset($_SESSION['model'])) {
    $model = $_SESSION['model'];
} else {
    $model = load_model($project);
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
                $contentFolder = $project['content']['folder'] . $_GET['type'] . '/';
                $contentFile = $contentFolder . $_GET['id'] . '.' . $project['content']['format'];
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
                    case 'date':
                        echo '<input type="date" name="' . $field['id'] . '" id="' . $field['id'] .
                            '" value="' . $value . '"></div>';
                        break;
                    case 'hidden':
                        echo '<input type="hidden" name="' . $field['id'] . '" id="' . $field['id'] .
                            '" value="' . $value . '">';
                        echo '<input disabled type="textfield" name="palceholder-' . $field['id'] . '" id="placeholder-' . $field['id'] .
                            '" value="' . $value . '"></div>';
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
            $contentFolder = $project['content']['folder'] . $_POST['type'] . '/';
            if (!is_dir($contentFolder)) {
                mkdir($contentFolder, 0777, true);
            }
            $contentFile = $contentFolder . $_POST['id'] . '.' . $project['content']['format'];
            switch ($project['content']['format']) {
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
        $contentFolder = $project['content']['folder'] . $_GET['type'] . '/';
        $contentFile = $contentFolder . $_GET['id'] . '.' . $project['content']['format'];
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
                $contentFolder = $project['content']['folder'] . $type['id'] . '/';
                if (is_dir($contentFolder)) {
                    $files = scandir($contentFolder);
                    foreach ($files as $file) {
                        if ($file !== '.' && $file !== '..') {
                            $contentFile = $contentFolder . $file;
                            $data = json_decode(file_get_contents($contentFile), true);
                            if (isset($data['id']) && $type['id']) {
                                $objects[$type['id']][$data['id']] = $type['id'] . '/' . $data['id'] . '.' . $project['content']['format'];
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
        // check if folder exists, if not create it.
        if (!is_dir($project['content']['folder'])) {
            mkdir($project['content']['folder'], 0777, true);
        }
        file_put_contents($project['content']['folder'] . 'index.json', json_encode($objects));

        // Available projects.
        echo '<h2>Available projects</h2>';
        $projectsFolder = 'projects';
        if (is_dir($projectsFolder)) {
            $files = scandir($projectsFolder);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    echo '<div>';
                    echo '<span><a href="' . $projectsFolder . '/' . $file . '">View</a> | ' .
                    '<a href="index.php?action=load&project=' . $file . '">Load</a></span>&nbsp;&nbsp;' .
                    $file . '</span>';
                    echo '</div>';
                }
            }
        }
        break;
        case 'load':
            // Load the project.
            $projectsFolder = 'projects';
            $project = json_decode(file_get_contents($projectsFolder . '/' . $_GET['project']), true);
            $_SESSION['model'] = load_models($project);
            $_SESSION['project'] = $project;
            // Redirect to the dashboard.
            header('Location: index.php');
            break;
}

exit;


// Load models function.
function load_models($project) {
    $model = [];
    foreach ($project['models'] as $m) {
        $model_part = load_model($m);
        // Merge the model parts recursively.
        $model = custom_array_merge_recursive($model, $model_part);

    }
    return $model;
}

// Custom function to merge arrays recursively with specific behavior for numeric keys.
function custom_array_merge_recursive($array1, $array2) {
    foreach ($array2 as $key => $value) {
        if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
            if (array_keys($value) === range(0, count($value) - 1)) {
                // If the array has numeric keys, replace it with the last element.
                $array1[$key] = end($value);
            } else {
                // Otherwise, merge recursively.
                $array1[$key] = custom_array_merge_recursive($array1[$key], $value);
            }
        } else {
            $array1[$key] = $value;
        }
    }
    return $array1;
}

// Load one model.
function load_model($m) {
    if (isset($m['folder']) &&
        isset($m['file'])) {
        $root_path = $m['folder'];
        $root_file = $m['file'];
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
        while (check_parents($model['types'])) {
            $model = add_parent_fields($model);
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

        return $model;
    }
    return [];
}

// Check if types have parents.
function check_parents($types) {
    foreach ($types as $type) {
        if (isset($type['parent'])) {
            return true;
        }
    }
    return false;
}

// Add fileds from parents.
function add_parent_fields($model) {
    $modified_types = $model['types'];
    foreach ($model['types'] as $key => $type) {
        if (!isset($type['parent'])) {
            foreach ($modified_types as $modified_key => $modified_type) {
                if (isset($modified_type['parent']) &&
                    // Defence from the stupid case when parent is the same as the child.
                    $modified_type['parent'] != $modified_key &&
                    $modified_type['parent'] === $key) {
                    // Add parent fields to the children types in the start of the array.
                    foreach ($type['fields'] as $field) {
                        if (isset($model['types'][$modified_key]['fields'][$field['id']])) {
                            $modified_types[$modified_key]['fields'][$field['id']] = $field;
                        }
                        else {
                            $modified_types[$modified_key][$field['id']] = $field;
                        }
                    }
                    unset($modified_types[$modified_key]['parent']);
                }
            }
        }
    }
    $model['types'] = $modified_types;
    return $model;
}