<?php

    /**
     *
     * Mock version of the PDOStatement class. Can be
     * used to get a string representing a "bound" version
     * of the query. Because PDO works using prepared statements,
     * this can provide only a rough representation of the
     * query, but this will usually be enough to check that
     * your query has been built as expected.
     *
     */
    class DummyPDOStatement {

        private $query = '';
        private $input_parameters = array();
        private $current_row = 1;
        
        public function __construct($statement) {
            $this->query = $statement;
        }

        public function execute($input_parameters=array()) {
            $this->input_parameters = $input_parameters;
        }

        public function fetch($fetch_style) {
            if ($this->current_row == 5) {
                return false;
            } else {
                $this->current_row++;
                return array('name' => 'Fred', 'age' => 10, 'id' => '1');
            }
        }

        public function get_query() {
            return $this->query;
        }

        public function get_parameters() {
            return $this->input_parameters;
        }

        public function get_bound_query() {
            $sql = $this->get_query();
            $sql = str_replace("?", "%s", $sql);

            $quoted_values = array();
            $values = $this->get_parameters();
            foreach ($values as $value) {
                $quoted_values[] = '"' . $value . '"';
            }
            return vsprintf($sql, $quoted_values);
        }

    }

    /**
     *
     * Mock database class implementing a subset
     * of the PDO API.
     *
     */
    class DummyPDO {

        private $last_query;
       
        public function __construct($connection_string="") {
        }

        public function setAttribute($attribute, $value) {
        }

        public function prepare($statement) {
            $this->last_query = new DummyPDOStatement($statement);
            return $this->last_query;
        }

        public function lastInsertId() {
            return 0;
        }

        public function get_last_query() {
            return $this->last_query->get_bound_query();
        }
    }

    /**
     *
     * Class to provide simple testing functionality
     *
     */
    class Tester {

        private static $passed_tests = array();
        private static $failed_tests = array();
        private static $db;

        /**
         * Set the dummy database connection to be
         * used by the class to capture the SQL strings
         */
        public static function set_db($db) {
            self::$db = $db;
        }

        /**
         * Format a line for printing. Detects
         * if the script is being run from the command
         * line or from a browser.
         */
        private static function format_line($line) {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                return "<p>$line</p>\n";
            } else {
                return "$line\n";
            }
        }

        /**
         * Report a passed test
         */
        private static function report_pass($test_name) {
            echo self::format_line("PASS: $test_name");
            self::$passed_tests[] = $test_name;
        }

        /**
         * Report a failed test
         */
        private static function report_failure($test_name, $query) {
            echo self::format_line("FAIL: $test_name");
            echo self::format_line("Expected: $query");
            echo self::format_line("Actual: " . self::$db->get_last_query());
            self::$failed_tests[] = $test_name;
        }

        /**
         * Print a summary of passed and failed test counts
         */
        public static function report() {
            $passed_count = count(self::$passed_tests);
            $failed_count = count(self::$failed_tests);
            echo self::format_line("$passed_count tests passed. $failed_count tests failed.");

            if ($failed_count != 0) {
                echo self::format_line("Failed tests: " . join(", ", self::$failed_tests));
            }
        }

        /**
         * Check the provided string is equal to the last
         * query generated by the dummy database class.
         */
        public static function check_equal($test_name, $query) {
            $last_query = self::$db->get_last_query();
            if ($query == self::$db->get_last_query()) {
                self::report_pass($test_name);
            } else {
                self::report_failure($test_name, $query);
            }
        }
    }
