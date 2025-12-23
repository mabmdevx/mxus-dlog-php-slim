<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

return function ($app) {

    // Home route
    $app->get('/', function (Request $request, Response $response, $args) {
        // Check if user is logged in
        if (!empty($_SESSION['user_id'] ?? null)) {
            return $response
                ->withStatus(302)
                ->withHeader('Location', '/dashboard');
        }
        
        return $response
            ->withStatus(302)
            ->withHeader('Location', '/login');
    })->setName('home');


    // Dashboard route
    $app->get('/dashboard', function (Request $request, Response $response, $args) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $view = Twig::fromRequest($request);
        $db = $this->get('db');
        $dataObjectModel = new DataObject($db);
        $moduleModel = new Module($db);
        $weightModel = new Weight($db);

        // Get all modules and add data object count
        $modules = [];
        if (!empty($_SESSION['user_uuid'] ?? null)) {
            $modules = $moduleModel->getAll();
            
            // Add data object count to each module
            foreach ($modules as &$mod) {
                $mod['data_object_count'] = $dataObjectModel->countByModuleUuidAndUserUuid(
                    $mod['module_uuid'],
                    $_SESSION['user_uuid']
                );
            }
        }

        // Get all data objects for the user (using user UUID)
        $dataObjects = [];
        if (!empty($_SESSION['user_uuid'] ?? null)) {
            $dataObjects = $dataObjectModel->getByUserUuid($_SESSION['user_uuid']);
            
            // Add record count to each data object
            foreach ($dataObjects as &$obj) {
                $obj['record_count'] = $weightModel->countByDataObjectUuidAndUserUuid(
                    $obj['data_object_uuid'],
                    $_SESSION['user_uuid']
                );
            }
        }
        
        return $view->render($response, 'auth_pages/dashboard.html', [
            'session' => $_SESSION ?? [],
            'modules' => $modules,
            'data_objects' => $dataObjects,
        ]);
    })->setName('dashboard');


    // Modules - List - GET
    $app->get('/modules-list', function (Request $request, Response $response) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $view = Twig::fromRequest($request);
        $db = $this->get('db');
        $moduleModel = new Module($db);
        $dataObjectModel = new DataObject($db);

        // Get all modules
        $modules = $moduleModel->getAll();

        // Add data object count to each module
        foreach ($modules as &$mod) {
            $mod['data_object_count'] = $dataObjectModel->countByModuleUuidAndUserUuid(
                $mod['module_uuid'],
                $_SESSION['user_uuid']
            );
        }

        return $view->render($response, 'auth_pages/modules_list.html', [
            'session' => $_SESSION ?? [],
            'modules' => $modules,
        ]);
    })->setName('modules-list');


    // Data Objects - List - GET
    $app->get('/data-objects-list', function (Request $request, Response $response) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $view = Twig::fromRequest($request);
        $db = $this->get('db');
        $dataObjectModel = new DataObject($db);

        // Get all data objects for the user (using user UUID)
        $dataObjects = $dataObjectModel->getByUserUuid($_SESSION['user_uuid']);

        return $view->render($response, 'auth_pages/data_objects_list.html', [
            'session' => $_SESSION ?? [],
            'data_objects' => $dataObjects,
        ]);
    })->setName('data-objects-list');


    // Data Object - Add - GET
    $app->get('/data-object-add', function (Request $request, Response $response) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $view = Twig::fromRequest($request);
        $db = $this->get('db');
        $moduleModel = new Module($db);
        $modules = $moduleModel->getAll();

        return $view->render($response, 'auth_pages/data_object_form.html', [
            'session' => $_SESSION ?? [],
            'data_object' => null,
            'modules' => $modules,
            'msg_error' => null,
            'msg_success' => null,
        ]);
    })->setName('data-object-add');


    // Data Object - Add - POST
    $app->post('/data-object-add', function (Request $request, Response $response) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $view = Twig::fromRequest($request);
        $data = $request->getParsedBody();
        $db = $this->get('db');
        $dataObjectModel = new DataObject($db);
        $moduleModel = new Module($db);
        $modules = $moduleModel->getAll();

        $dataObjectName = $data['data_object_name'] ?? '';
        $dataObjectDesc = $data['data_object_desc'] ?? '';
        $dataObjectUnit = $data['data_object_unit'] ?? '';
        $dataObjectModuleUuid = $data['data_object_module_uuid'] ?? '';
        $error = null;
        $success = false;

        // Validation
        if (empty($dataObjectName)) {
            $error = 'Data object name is required';
        } elseif (empty($dataObjectUnit)) {
            $error = 'Unit is required';
        } elseif (empty($dataObjectModuleUuid)) {
            $error = 'Module is required';
        } else {
            try {
                $dataObjectModel->create([
                    'data_object_name' => $dataObjectName,
                    'data_object_desc' => $dataObjectDesc,
                    'data_object_unit' => $dataObjectUnit,
                    'data_object_module_uuid' => $dataObjectModuleUuid,
                    'created_by' => $_SESSION['user_uuid'],
                ]);
                $success = true;
                // Redirect to data objects list
                return $response
                    ->withStatus(302)
                    ->withHeader('Location', '/data-objects-list');
            } catch (Exception $e) {
                $error = 'Failed to create data object: ' . $e->getMessage();
            }
        }

        return $view->render($response, 'auth_pages/data_object_form.html', [
            'session' => $_SESSION ?? [],
            'data_object' => null,
            'modules' => $modules,
            'msg_error' => $error,
            'msg_success' => $success ? 'Data object created successfully!' : null,
        ]);
    });


    // Data Object - Update - GET
    $app->get('/data-object-update/{data_object_uuid}', function (Request $request, Response $response, $args) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $dataObjectUuid = $args['data_object_uuid'] ?? '';
        $view = Twig::fromRequest($request);
        $db = $this->get('db');
        $dataObjectModel = new DataObject($db);
        $moduleModel = new Module($db);
        $modules = $moduleModel->getAll();

        // Get the data object
        $dataObject = $dataObjectModel->findByUuid($dataObjectUuid);
        
        if (!$dataObject) {
            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'text/plain')
                ->write('Data object not found');
        }

        // Verify user owns this data object
        if ($dataObject['created_by'] !== $_SESSION['user_uuid']) {
            return $response
                ->withStatus(403)
                ->withHeader('Content-Type', 'text/plain')
                ->write('You do not have permission to update this data object');
        }

        return $view->render($response, 'auth_pages/data_object_form.html', [
            'session' => $_SESSION ?? [],
            'data_object' => $dataObject,
            'modules' => $modules,
            'msg_error' => null,
            'msg_success' => null,
        ]);
    })->setName('data-object-update');


    // Data Object - Update - POST
    $app->post('/data-object-update/{data_object_uuid}', function (Request $request, Response $response, $args) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $dataObjectUuid = $args['data_object_uuid'] ?? '';
        $view = Twig::fromRequest($request);
        $data = $request->getParsedBody();
        $db = $this->get('db');
        $dataObjectModel = new DataObject($db);
        $moduleModel = new Module($db);
        $modules = $moduleModel->getAll();

        // Get the data object
        $dataObject = $dataObjectModel->findByUuid($dataObjectUuid);
        
        if (!$dataObject) {
            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'text/plain')
                ->write('Data object not found');
        }

        // Verify user owns this data object
        if ($dataObject['created_by'] !== $_SESSION['user_uuid']) {
            return $response
                ->withStatus(403)
                ->withHeader('Content-Type', 'text/plain')
                ->write('You do not have permission to update this data object');
        }

        $dataObjectName = $data['data_object_name'] ?? '';
        $dataObjectDesc = $data['data_object_desc'] ?? '';
        $dataObjectUnit = $data['data_object_unit'] ?? '';
        $dataObjectModuleUuid = $data['data_object_module_uuid'] ?? '';
        $error = null;
        $success = false;

        // Validation
        if (empty($dataObjectName)) {
            $error = 'Data object name is required';
        } elseif (empty($dataObjectUnit)) {
            $error = 'Unit is required';
        } elseif (empty($dataObjectModuleUuid)) {
            $error = 'Module is required';
        } else {
            try {
                $dataObjectModel->update($dataObject['data_object_id'], [
                    'data_object_name' => $dataObjectName,
                    'data_object_desc' => $dataObjectDesc,
                    'data_object_unit' => $dataObjectUnit,
                    'data_object_module_uuid' => $dataObjectModuleUuid,
                    'updated_by' => $_SESSION['user_uuid'],
                ]);
                $success = true;
                // Redirect to data objects list
                return $response
                    ->withStatus(302)
                    ->withHeader('Location', '/data-objects-list');
            } catch (Exception $e) {
                $error = 'Failed to update data object: ' . $e->getMessage();
            }
        }

        return $view->render($response, 'auth_pages/data_object_form.html', [
            'session' => $_SESSION ?? [],
            'data_object' => $dataObject,
            'modules' => $modules,
            'msg_error' => $error,
            'msg_success' => $success ? 'Data object updated successfully!' : null,
        ]);
    })->setName('data-object-update-post');


    // Data Object - Delete - GET
    $app->get('/data-object-delete/{data_object_uuid}', function (Request $request, Response $response, $args) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $dataObjectUuid = $args['data_object_uuid'] ?? '';
        $db = $this->get('db');
        $dataObjectModel = new DataObject($db);

        try {
            // Get the data object to verify ownership
            $dataObject = $dataObjectModel->findByUuid($dataObjectUuid);
            
            if (!$dataObject) {
                return $response
                    ->withStatus(404)
                    ->withHeader('Content-Type', 'text/plain')
                    ->write('Data object not found');
            }

            // Verify user owns this data object
            if ($dataObject['created_by'] !== $_SESSION['user_uuid']) {
                return $response
                    ->withStatus(403)
                    ->withHeader('Content-Type', 'text/plain')
                    ->write('You do not have permission to delete this data object');
            }

            // Delete the data object (soft delete)
            $dataObjectModel->delete($dataObject['data_object_id']);

            // Redirect back to data objects list
            return $response
                ->withStatus(302)
                ->withHeader('Location', '/data-objects-list');
        } catch (Exception $e) {
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'text/plain')
                ->write('Error deleting data object: ' . $e->getMessage());
        }
    })->setName('data-object-delete');


    // Weight - List - GET
    $app->get('/weight-list', function (Request $request, Response $response) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $view = Twig::fromRequest($request);
        $db = $this->get('db');
        $weightModel = new Weight($db);
        $dataObjectModel = new DataObject($db);

        // Get all data objects for the user (for dropdown)
        $allDataObjects = $dataObjectModel->getByUserUuid($_SESSION['user_uuid']);

        // Get selected data object UUID from query params
        $selectedDataObjectUuid = $request->getQueryParams()['data_object_uuid'] ?? '';
        $selectedDataObjectName = '';
        $weights = [];

        // Only fetch weights if data object is selected
        if (!empty($selectedDataObjectUuid)) {
            $weights = $weightModel->getByDataObjectUuidAndUserUuid($selectedDataObjectUuid, $_SESSION['user_uuid']);
            
            // Get the selected data object name
            $selectedDataObject = $dataObjectModel->findByUuid($selectedDataObjectUuid);
            if ($selectedDataObject) {
                $selectedDataObjectName = $selectedDataObject['data_object_name'];
            }
        }

        return $view->render($response, 'auth_pages/weight_list.html', [
            'session' => $_SESSION ?? [],
            'weights' => $weights,
            'all_data_objects' => $allDataObjects,
            'selected_data_object_uuid' => $selectedDataObjectUuid,
            'selected_data_object_name' => $selectedDataObjectName,
        ]);
    })->setName('weight-list');


    // Weight - Add - GET
    $app->get('/weight-add', function (Request $request, Response $response) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $view = Twig::fromRequest($request);
        $db = $this->get('db');
        $dataObjectModel = new DataObject($db);

        // Get all data objects for the user (using user UUID)
        $dataObjects = $dataObjectModel->getByUserUuid($_SESSION['user_uuid']);

        return $view->render($response, 'auth_pages/weight_form.html', [
            'session' => $_SESSION ?? [],
            'weight' => null,
            'data_objects' => $dataObjects,
            'msg_error' => null,
            'msg_success' => null,
        ]);
    })->setName('weight-add');


    // Weight - Add - POST
    $app->post('/weight-add', function (Request $request, Response $response) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $view = Twig::fromRequest($request);
        $data = $request->getParsedBody();
        $db = $this->get('db');
        $weightModel = new Weight($db);
        $dataObjectModel = new DataObject($db);

        $weightVal = $data['weight_val'] ?? '';
        $dataObjectUuid = $data['weight_data_object_uuid'] ?? '';
        $weightTimestamp = $data['weight_timestamp'] ?? '';
        $error = null;
        $success = false;

        // Validation
        if (empty($dataObjectUuid)) {
            $error = 'Please select a data object';
        } elseif (empty($weightVal)) {
            $error = 'Weight value is required';
        } elseif (!is_numeric($weightVal)) {
            $error = 'Weight value must be a number';
        } elseif (empty($weightTimestamp)) {
            $error = 'Date & time is required';
        } else {
            try {
                // Convert datetime-local format to datetime format
                $timestamp = str_replace('T', ' ', $weightTimestamp);
                $weightModel->create([
                    'weight_val' => $weightVal,
                    'weight_data_object_uuid' => $dataObjectUuid,
                    'weight_timestamp' => $timestamp,
                    'created_by' => $_SESSION['user_uuid'],
                ]);
                $success = true;
                // Redirect to weight list filtered by this data object
                return $response
                    ->withStatus(302)
                    ->withHeader('Location', '/weight-list?data_object_uuid=' . urlencode($dataObjectUuid));
            } catch (Exception $e) {
                $error = 'Failed to create weight entry: ' . $e->getMessage();
            }
        }

        // Get data objects for form (using user UUID)
        $dataObjects = $dataObjectModel->getByUserUuid($_SESSION['user_uuid']);

        return $view->render($response, 'auth_pages/weight_form.html', [
            'session' => $_SESSION ?? [],
            'weight' => null,
            'data_objects' => $dataObjects,
            'msg_error' => $error,
            'msg_success' => $success ? 'Weight entry created successfully!' : null,
        ]);
    });


    // Weight - Update - GET
    $app->get('/weight-update/{weight_uuid}', function (Request $request, Response $response, $args) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $weightUuid = $args['weight_uuid'] ?? '';
        $view = Twig::fromRequest($request);
        $db = $this->get('db');
        $weightModel = new Weight($db);
        $dataObjectModel = new DataObject($db);

        // Get the weight entry
        $weight = $weightModel->findByUuid($weightUuid);
        
        if (!$weight) {
            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'text/plain')
                ->write('Weight entry not found');
        }

        // Verify user owns this weight entry
        if ($weight['created_by'] !== $_SESSION['user_uuid']) {
            return $response
                ->withStatus(403)
                ->withHeader('Content-Type', 'text/plain')
                ->write('You do not have permission to update this weight entry');
        }

        // Get all data objects for the user (using user UUID)
        $dataObjects = $dataObjectModel->getByUserUuid($_SESSION['user_uuid']);

        return $view->render($response, 'auth_pages/weight_form.html', [
            'session' => $_SESSION ?? [],
            'weight' => $weight,
            'data_objects' => $dataObjects,
            'msg_error' => null,
            'msg_success' => null,
        ]);
    })->setName('weight-update');


    // Weight - Update - POST
    $app->post('/weight-update/{weight_uuid}', function (Request $request, Response $response, $args) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $weightUuid = $args['weight_uuid'] ?? '';
        $view = Twig::fromRequest($request);
        $data = $request->getParsedBody();
        $db = $this->get('db');
        $weightModel = new Weight($db);
        $dataObjectModel = new DataObject($db);

        // Get the weight entry
        $weight = $weightModel->findByUuid($weightUuid);
        
        if (!$weight) {
            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'text/plain')
                ->write('Weight entry not found');
        }

        // Verify user owns this weight entry
        if ($weight['created_by'] !== $_SESSION['user_uuid']) {
            return $response
                ->withStatus(403)
                ->withHeader('Content-Type', 'text/plain')
                ->write('You do not have permission to update this weight entry');
        }

        $weightVal = $data['weight_val'] ?? '';
        $dataObjectUuid = $data['weight_data_object_uuid'] ?? '';
        $weightTimestamp = $data['weight_timestamp'] ?? '';
        $error = null;
        $success = false;

        // Validation
        if (empty($dataObjectUuid)) {
            $error = 'Please select a data object';
        } elseif (empty($weightVal)) {
            $error = 'Weight value is required';
        } elseif (!is_numeric($weightVal)) {
            $error = 'Weight value must be a number';
        } elseif (empty($weightTimestamp)) {
            $error = 'Date & time is required';
        } else {
            try {
                // Convert datetime-local format to datetime format
                $timestamp = str_replace('T', ' ', $weightTimestamp);
                $weightModel->update($weight['weight_id'], [
                    'weight_val' => $weightVal,
                    'weight_data_object_uuid' => $dataObjectUuid,
                    'weight_timestamp' => $timestamp,
                    'updated_by' => $_SESSION['user_uuid'],
                ]);
                $success = true;
                // Redirect to weight list filtered by this data object
                return $response
                    ->withStatus(302)
                    ->withHeader('Location', '/weight-list?data_object_uuid=' . urlencode($dataObjectUuid));
            } catch (Exception $e) {
                $error = 'Failed to update weight entry: ' . $e->getMessage();
            }
        }

        // Get data objects for form (using user UUID)
        $dataObjects = $dataObjectModel->getByUserUuid($_SESSION['user_uuid']);

        return $view->render($response, 'auth_pages/weight_form.html', [
            'session' => $_SESSION ?? [],
            'weight' => $weight,
            'data_objects' => $dataObjects,
            'msg_error' => $error,
            'msg_success' => $success ? 'Weight entry updated successfully!' : null,
        ]);
    })->setName('weight-update-post');


    // Weight - Delete - GET
    $app->get('/weight-delete/{weight_uuid}', function (Request $request, Response $response, $args) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $weightUuid = $args['weight_uuid'] ?? '';
        $db = $this->get('db');
        $weightModel = new Weight($db);

        try {
            // Get the weight entry to verify ownership
            $weight = $weightModel->findByUuid($weightUuid);
            
            if (!$weight) {
                return $response
                    ->withStatus(404)
                    ->withHeader('Content-Type', 'text/plain')
                    ->write('Weight entry not found');
            }

            // Verify user owns this weight entry
            if ($weight['created_by'] !== $_SESSION['user_uuid']) {
                return $response
                    ->withStatus(403)
                    ->withHeader('Content-Type', 'text/plain')
                    ->write('You do not have permission to delete this weight entry');
            }

            // Delete the weight entry (soft delete)
            $weightModel->deleteByUuid($weightUuid);

            // Redirect back to weight list with the original data object selection
            $dataObjectUuid = $weight['weight_data_object_uuid'];
            return $response
                ->withStatus(302)
                ->withHeader('Location', '/weight-list?data_object_uuid=' . urlencode($dataObjectUuid));
        } catch (Exception $e) {
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'text/plain')
                ->write('Error deleting weight entry: ' . $e->getMessage());
        }
    })->setName('weight-delete');


    // Weight - Analytics - GET
    $app->get('/weight-analytics', function (Request $request, Response $response) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $view = Twig::fromRequest($request);
        $db = $this->get('db');
        $dataObjectModel = new DataObject($db);
        $weightModel = new Weight($db);

        // Get all data objects for the user (for dropdown)
        $allDataObjects = $dataObjectModel->getByUserUuid($_SESSION['user_uuid']);

        // Get selected data object UUID from query params
        $selectedDataObjectUuid = $request->getQueryParams()['data_object_uuid'] ?? '';
        $selectedDataObjectName = '';
        $weights = [];
        $chartData = [];
        $stats = [
            'total' => 0,
            'min' => 0,
            'max' => 0,
            'average' => 0
        ];

        // Get weights - filter by data object if provided
        if (!empty($selectedDataObjectUuid)) {
            $weights = $weightModel->getByDataObjectUuidAndUserUuid($selectedDataObjectUuid, $_SESSION['user_uuid']);
            
            // Get the selected data object name
            $selectedDataObject = $dataObjectModel->findByUuid($selectedDataObjectUuid);
            if ($selectedDataObject) {
                $selectedDataObjectName = $selectedDataObject['data_object_name'];
            }

            // Prepare chart data - convert to JSON for D3
            $chartData = array_map(function($weight) {
                return [
                    'date' => $weight['weight_timestamp'],
                    'value' => (float)$weight['weight_val']
                ];
            }, $weights);
            // Sort by date ascending for line graph
            usort($chartData, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });

            // Calculate statistics
            if (count($weights) > 0) {
                $values = array_map(function($w) { return (float)$w['weight_val']; }, $weights);
                $stats['total'] = count($weights);
                $stats['min'] = min($values);
                $stats['max'] = max($values);
                $stats['average'] = array_sum($values) / count($values);
            }
        }

        return $view->render($response, 'auth_pages/weight_analytics.html', [
            'session' => $_SESSION ?? [],
            'all_data_objects' => $allDataObjects,
            'selected_data_object_uuid' => $selectedDataObjectUuid,
            'selected_data_object_name' => $selectedDataObjectName,
            'weights' => $weights,
            'chart_data' => json_encode($chartData),
            'stats' => $stats,
        ]);
    })->setName('weight-analytics');


};

