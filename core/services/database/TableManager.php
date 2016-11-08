<?php
namespace EventEspresso\core\services\database;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct script access allowed');



/**
 * Class TableManager
 * For performing mysql database table schema manipulation
 *
 * @package               Event Espresso
 * @subpackage
 * @author                Mike Nelson
 * @since                 $VID:$
 */
class TableManager extends \EE_Base
{

    /**
     * @var TableAnalysis $table_analysis
     */
    private $table_analysis;



    /**
     * TableManager constructor.
     *
     * @param TableAnalysis $TableAnalysis
     */
    public function __construct(TableAnalysis $TableAnalysis)
    {
        $this->table_analysis = $TableAnalysis;
    }



    /**
     * Gets the injected table analyzer, or throws an exception
     *
     * @return TableAnalysis
     * @throws \EE_Error
     */
    protected function getTableAnalysis()
    {
        if ($this->table_analysis instanceof TableAnalysis) {
            return $this->table_analysis;
        } else {
            throw new \EE_Error(
                sprintf(
                    __('Table analysis class on class %1$s is not set properly.', 'event_espresso'),
                    get_class($this)
                )
            );
        }
    }



    /**
     * @param string $table_name which can optionally start with $wpdb->prefix or not
     * @param string $column_name
     * @param string $column_info
     * @return bool|false|int
     */
    public function addColumn($table_name, $column_name, $column_info = 'INT UNSIGNED NOT NULL')
    {
        if (apply_filters('FHEE__EEH_Activation__add_column_if_it_doesnt_exist__short_circuit', false)) {
            return false;
        }
        global $wpdb;
        $full_table_name = $this->getTableAnalysis()->ensureTableNameHasPrefix($table_name);
        $columns = $this->getTableColumns($table_name);
        if ( ! in_array($column_name, $columns)) {
            $alter_query = "ALTER TABLE $full_table_name ADD $column_name $column_info";
            return $wpdb->query($alter_query);
        }
        return true;
    }



    /**
     * Gets the name of all columns on the  table. $table_name can
     * optionally start with $wpdb->prefix or not
     *
     * @global \wpdb $wpdb
     * @param string $table_name
     * @return array
     */
    public function getTableColumns($table_name)
    {
        global $wpdb;
        $table_name = $this->getTableAnalysis()->ensureTableNameHasPrefix($table_name);
        $fieldArray = array();
        if ( ! empty($table_name)) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name ");
            if ($columns !== false) {
                foreach ($columns as $column) {
                    $fieldArray[] = $column->Field;
                }
            }
        }
        return $fieldArray;
    }



    /**
     * Drops the specified table from the database. $table_name can
     * optionally start with $wpdb->prefix or not
     *
     * @global \wpdb $wpdb
     * @param string $table_name
     * @return int
     */
    public function dropTable($table_name)
    {
        global $wpdb;
        if ($this->getTableAnalysis()->tableExists($table_name)) {
            $table_name = $this->getTableAnalysis()->ensureTableNameHasPrefix($table_name);
            return $wpdb->query("DROP TABLE IF EXISTS $table_name");
        }
        return 0;
    }



    /**
     * Drops all the tables mentioned in a single MYSQL query. Double-checks
     * each table name provided has a wpdb prefix attached, and that it exists.
     * Returns the list actually deleted
     *
     * @global WPDB $wpdb
     * @param array $table_names
     * @return array of table names which we deleted
     */
    public function dropTables($table_names)
    {
        $tables_to_delete = array();
        foreach ($table_names as $table_name) {
            $table_name = $this->getTableAnalysis()->ensureTableNameHasPrefix($table_name);
            if ($this->getTableAnalysis()->tableExists($table_name)) {
                $tables_to_delete[] = $table_name;
            }
        }
        global $wpdb;
        $wpdb->query('DROP TABLE ' . implode(', ', $tables_to_delete));
        return $tables_to_delete;
    }



    /**
     * Drops the specified index from the specified table. $table_name can
     * optionally start with $wpdb->prefix or not
     *
     * @global \wpdb $wpdb
     * @param string $table_name
     * @param string $indexName
     * @return int
     */
    public function dropIndex($table_name, $indexName)
    {
        if (apply_filters('FHEE__EEH_Activation__drop_index__short_circuit', false)) {
            return false;
        }
        global $wpdb;
        $table_name = $this->getTableAnalysis()->ensureTableNameHasPrefix($table_name);
        $index_exists_query = "SHOW INDEX FROM $table_name WHERE Key_name = '$indexName'";
        if (
            $this->getTableAnalysis()->tableExists($table_name)
            && $wpdb->get_var($index_exists_query)
               === $table_name //using get_var with the $index_exists_query returns the table's name
        ) {
            return $wpdb->query("ALTER TABLE $table_name DROP INDEX $indexName");
        }
        return 0;
    }



    /**
     * Just creates the requested table. $table_name can
     * optionally start with $wpdb->prefix or not
     *
     * @param string $table_name
     * @param string $createSql defining the table's columns and indexes
     * @param string $engine    (no need to specify "ENGINE=", that's implied)
     * @return void
     * @throws \EE_Error
     */
    public function createTable($table_name, $createSql, $engine = 'MyISAM')
    {
        // does $sql contain valid column information? ( LPT: https://regex101.com/ is great for working out regex patterns )
        if (preg_match('((((.*?))(,\s))+)', $createSql, $valid_column_data)) {
            $table_name = $this->getTableAnalysis()->ensureTableNameHasPrefix($table_name);
            $SQL = "CREATE TABLE $table_name ( $createSql ) ENGINE=$engine DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
            /** @var \wpdb $wpdb */
            global $wpdb;
            //get $wpdb to echo errors, but buffer them. This way at least WE know an error
            //happened. And then we can choose to tell the end user
            $old_show_errors_policy = $wpdb->show_errors(true);
            $old_error_suppression_policy = $wpdb->suppress_errors(false);
            ob_start();
            dbDelta($SQL);
            $output = ob_get_contents();
            ob_end_clean();
            $wpdb->show_errors($old_show_errors_policy);
            $wpdb->suppress_errors($old_error_suppression_policy);
            if ( ! empty($output)) {
                throw new \EE_Error($output);
            }
        } else {
            throw new \EE_Error(
                sprintf(
                    __('The following table creation SQL does not contain valid information about the table columns: %1$s %2$s',
                        'event_espresso'),
                    '<br />',
                    $createSql
                )
            );
        }
    }

}
