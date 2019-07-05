<?php

/* FetcherGRIDMI 1.0.0
 * ------------------
 * Class for automated work
 * with MySQL database for PHP
 * */

class FetcherGRIDMI {

    private static function selectPrimaryAndSecondaryVariables($query) {
        preg_match('/\([A-z0-9]+\)/i', $query, $primary);
        preg_match('/\([A-z0-9]+\.[A-z0-9]+\)/i', $query, $secondary);
        return array('primary' => $primary, 'secondary' => $secondary);
    }

    /**
     * @param $descriptor mysqli
     * @param $query string
     * @param $primaryVariables array
     * @param $secondaryVariables array
     * @return string
     */

    private static function onReplaceVariables($descriptor, $query, $primaryVariables, $secondaryVariables) {

        // Capture variables from the query string
        $variables = self::selectPrimaryAndSecondaryVariables($query);

        // Replace primary variables
        foreach ($variables['primary'] as $value) {
            if (isset($primaryVariables[$key = substr($value, 1, -1)])) {
                if (is_scalar($variable = $primaryVariables[$key])) {
                    $query = str_replace($value, $descriptor -> real_escape_string($variable), $query);
                }
            }
        }

        // Replace secondary variables
        foreach ($variables['secondary'] as $value) {
            if (is_array($keys = explode('.',  substr($value, 1, -1))) && count($keys) == 2) {
                if (isset($secondaryVariables[$keys[0]][$keys[1]]) && is_scalar($variable = $secondaryVariables[$keys[0]][$keys[1]])) {
                    $query = str_replace($value, $descriptor -> real_escape_string($variable), $query);
                }
            }
        }

        // Return result of replacement
        return $query;

    }

    public static function onSelect($descriptor, $schema, $primaryVariables = array()) {

        // Data array
        $data = array();

        // Check the correctness of method parameters
        if ($descriptor instanceof mysqli && $schema instanceof stdClass) {

            // Function to force a cast to a specific type
            $onForceType = function ($type, $data) {

                // Check data and type
                if (is_scalar($data) && is_string($type)) {
                    switch (strtolower($type)) {
                        case 'string':
                            return strval($data);
                        case 'double':
                            return doubleval($data);
                        case 'float':
                            return floatval($data);
                        case 'integer':
                            return intval($data);
                    }
                }

                // Conversion error
                return null;

            };

            // The function of fetching data from the database
            $onFetch = function ($onFetch, $schema, $secondaryVariables = null) use ($onForceType, $descriptor, $primaryVariables) {

                // Data array
                $data = array();

                // Check SQL query string
                if (isset($schema -> query) && is_string($schema -> query)) {

                    // Override query variables
                    $schema -> query = FetcherGRIDMI::onReplaceVariables($descriptor, $schema -> query, $primaryVariables, $secondaryVariables);

                    // Run database query
                    if (is_object($query = $descriptor -> query($schema -> query))) {

                        // Select all database response rows
                        while (is_array($item = $query -> fetch_assoc())) {

                            // Check for current fetch properties
                            if (isset($schema -> properties) && is_object($schema -> properties)) {

                                // Initialize references
                                $links = is_array($secondaryVariables) ? $secondaryVariables : array();

                                // Set fetch ID
                                if (isset($schema -> id) && is_string($schema -> id)) {
                                    $links[$schema -> id] = &$item;
                                }

                                // Initialize the properties of the current selection
                                foreach ($schema -> properties as $key => $value) if (is_object($value)) {
                                    $item[$key] = $onFetch($onFetch, clone $value, $links);
                                }

                            }

                            // Check the availability of properties to exclude
                            if (isset($schema -> exclude) && is_array($schema -> exclude)) {

                                // Remove properties specified in the scheme
                                foreach ($schema -> exclude as $key) if (is_string($key)) unset($item[$key]);

                            }

                            // Check availability of properties for type conversion
                            if (isset($schema -> types) && is_object($schema -> types)) {

                                // Convert the selection property to a specific type
                                foreach ($schema -> types as $key => $value) {
                                    if (isset($item[$key])) $item[$key] = $onForceType($value, $item[$key]);
                                }

                            }

                            // Selection item in selection array
                            $data[] = $item;

                        }

                    }

                }

                // Return data array
                return $data;

            };

            // Override the output array
            $data = $onFetch($onFetch, $schema);

        }

        // Return the final result of the fetch
        return $data;

    }

}
