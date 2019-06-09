<?php
/**
 *    Copyright (c) 2019. - Eighty / 20 Results by Wicked Strong Chicks. ALL RIGHTS RESERVED
 *
 *     This program is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace E20R\Member_Directory\Database;


use E20R\Member_Directory\E20R_Directory_For_PMPro;
use E20R\Utilities\Utilities;

if ( ! class_exists( 'E20R\Member_Directory\Database\Select' ) ) {
	
	/**
	 * Class Select - Generate SQL Select statement consistently
	 *
	 * @package E20R\Member_Directory\Database
	 *
	 * NOTE: Does NOT support the WINDOW attribute/statement!
	 */
	class Select {
		
		/**
		 * SQL statement (string)
		 *
		 * @var string $sql
		 */
		private $sql = '';
		
		/**
		 * SQL statement qualifiers (DISTINCT|DISTINCTROW|etc)
		 *
		 * @var array $attributes
		 */
		private $attributes = array();
		
		/**
		 * Columns to return
		 *
		 * @var array $columns
		 */
		private $columns = array();
		
		/**
		 * Table to search (FROM)
		 *
		 * @var null|string $from
		 */
		private $from = null;
		
		/**
		 * List of JOIN statements for SQL query
		 *
		 * @var array $joins
		 */
		private $joins = array();
		
		/**
		 * The WHERE clauses to use
		 *
		 * @var array $where_clauses
		 */
		private $where_clauses = array();
		
		/**
		 * The GROUP clauses to use
		 *
		 * @var array $group_clauses
		 */
		private $group_clauses = array();
		
		/**
		 * The HAVING clauses to use
		 *
		 * @var array $having_clauses
		 */
		private $having_clauses = array();
		
		/**
		 * All ORDER BY statements
		 *
		 * @var array $order_by
		 */
		private $order_by = array();
		
		/**
		 * The sort order (for the ORDER BY statement)
		 *
		 * @var string $order_by_order
		 */
		private $order_by_order = 'ASC';
		/**
		 * LIMIT statement(s)
		 *
		 * @var array $limit
		 */
		private $limit = array();
		
		/**
		 * Select constructor.
		 */
		public function __construct() {
		}
		
		/**
		 * Verify the table name and add it
		 *
		 * @param array $table_info - array( 'name' => '', 'alias' => '' )
		 *
		 * @return bool
		 */
		public function addFrom( $table_info ) {
			
			global $wpdb;
			
			if ( empty( $table_info ) ) {
				return false;
			}
			
			$required = array( 'name' );
			
			if ( false === $this->hasRequiredInfo( $table_info, $required ) ) {
				return false;
			}
			
			$table_info = $this->sanitizeData( $table_info );
			
			// Verify that table name uses WP format
			if ( 1 !== preg_match( "/^{$wpdb->prefix}/", trim( $table_info['name'] ) ) ) {
				$table_info['name'] = "{$wpdb->prefix}{$table_info['name']}";
			}
			
			// Save table name & return true if it exists in local DB
			if ( true === $this->tableExists( $table_info['name'] ) ) {
				$this->from = $table_info;
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Add list of column_info to SELECT statement
		 *
		 * @param array $column_array
		 *
		 * @return bool
		 */
		public function addColumns( $column_array ) {
			
			if ( ! is_array( $column_array ) ) {
				return false;
			}
			
			$required = array( 'column', 'order' );
			$utils    = Utilities::get_instance();
			
			foreach ( $column_array as $column_info ) {
				
				if ( false === $this->hasRequiredInfo( $column_info, $required ) ) {
					$utils->log( "Cannot add a column" );
					
					return false;
				}
				
				$this->addColumn( $column_info );
			}
			
			return true;
		}
		
		/**
		 * Does the clause contain the required fields
		 *
		 * @param array $clause
		 * @param array $required
		 *
		 * @return bool
		 */
		private function hasRequiredInfo( $clause, $required ) {
			
			$utils = Utilities::get_instance();
			
			foreach ( $required as $field_name ) {
				
				if ( ! isset( $clause[ $field_name ] ) ) {
					
					$msg = sprintf(
						__( "Error: Required field for the #%d WHERE clause is missing (field name: %s)", Utilities::$plugin_slug ),
						$clause['order'],
						$field_name
					);
					
					$utils->log( $msg );
					$utils->add_message( $msg, 'error', 'backend' );
					
					return false;
				}
			}
		}
		
		/**
		 * Add a column to the SELECT statement
		 *
		 * @param array $column_info - array( 'order' => 0, 'prefix' => '', 'name' => '', 'alias' => '' );
		 */
		public function addColumn( $column_info ) {
			
			$column_info = $this->sanitizeData( $column_info );
			
			$this->columns[ $column_info['order'] ] = $column_info;
			ksort( $column_info );
		}
		
		/**
		 * Default settings for the WHERE clause (types: 'in', 'like', 'standard' )
		 *
		 * @param string $type
		 *
		 * @return mixed
		 */
		public function whereSettings( $type = 'standard' ) {
			
			$clauses = array(
				'in'       => array(
					'order'         => 0,
					/* Valid: AND / OR */
					'operator'      => 'AND',
					'type'          => 'in',
					'invert'        => false,
					'value'         => array(),
					'variable_type' => 'string', // string|numeric
					'prefix'        => '',
					'column'        => '',
					'sub_clause'    => array(),
					'multi_clause'  => false,
				),
				'like'     => array(
					'order'         => 0,
					/* Valid: AND / OR */
					'operator'      => 'AND',
					'type'          => 'like',
					'invert'        => false,
					'column'        => '',
					'prefix'        => '',
					'value'         => '',
					'variable_type' => 'string', // string|numeric
					'fuzzy_search'  => true, // Default behavior from the plugin (add-on)
					'sub_clause'    => array(),
					'multi_clause'  => false,
				),
				'standard' => array(
					/* Valid: AND / OR */
					'order'         => 0,
					'operator'      => 'AND',
					'type'          => 'standard',
					'column'        => '',
					'prefix'        => '',
					'value'         => '',
					'comparison'    => '=',
					'variable_type' => 'string', // string|numeric
					'sub_clause'    => array(),
					'multi_clause'  => false,
				),
			);
			
			return $clauses[ $type ];
		}
		
		/**
		 * Add a SQL WHERE clause to the list (ordered)
		 *
		 * @param array $clause
		 *
		 * @return bool
		 */
		public function addWhere( $clause ) {
			
			$utils  = Utilities::get_instance();
			$clause = $this->sanitizeData( $clause );
			
			// Validate that the supplied WHERE clause contains the required info...
			$required = array(
				'comparison',
				'column',
				'operator',
				'order',
				'value',
				'variable_type',
			);
			
			if ( false === $this->hasRequiredInfo( $clause, $required ) ) {
				$utils->log( "Cannot add WHERE clause # {$clause['order']}" );
				
				return false;
			}
			
			// Make sure the sub clauses are ordered
			if ( isset( $clause['sub_clause'] ) && ! empty( $clause['sub_clause'] ) ) {
				ksort( $clause['sub_clause'] );
			}
			
			$this->where_clauses[ $clause['order'] ] = $clause;
			
			// Sort by order
			ksort( $this->where_clauses );
		}
		
		/**
		 * Add attribute(s) to the beginning of the SELECT statement
		 *
		 * @param $attribute
		 */
		public function addAttribute( $attribute ) {
			
			$attribute = $this->sanitizeData( $attribute );
			
			$attribute = strtoupper( array_pop( $attribute ) );
			
			if ( empty( $this->attributes ) ) {
				$this->attributes[] = $attribute;
				
				return;
			}
			
			// Add SELECT query flag
			if ( 1 === preg_match( '/HIGH_PRIORITY|STRAIGHT_JOIN|SQL_SMALL_RESULT|SQL_BIG_RESULT|SQL_BUFFER_RESULT|SQL_NO_CACHE|SQL_CALC_FOUND_ROWS/i', $attribute ) ) {
				
				if ( ! in_array( $attribute, $this->attributes ) ) {
					$this->attributes[] = $attribute;
				}
			}
			
			// Add row limiter
			if ( 1 === preg_match( '/ALL|DISTINCT|DISTINCTROW/i', $attribute ) ) {
				
				if ( false === $this->resultSelectionAdded( $attribute ) ) {
					// Insert as first entry
					array_unshift( $this->attributes, $attribute );
				}
			}
		}
		
		/**
		 * See if the user has added one of the 'ALL', 'DISTINCT' or 'DISTINCTROW' SELECT modifiers already
		 *
		 * @param $attribute
		 *
		 * @return bool|int
		 */
		private function resultSelectionAdded( $attribute ) {
			
			$row_attributes = array( 'ALL', 'DISTINCT', 'DISTINCTROW' );
			
			if ( ! in_array( $attribute, $row_attributes ) ) {
				return false;
			}
			
			foreach ( $this->attributes as $in_list_attr ) {
				
				if ( in_array( $in_list_attr, $row_attributes ) ) {
					return false;
				}
			}
			
			return false;
		}
		
		/**
		 * Add the prefix attributes to the SQL SELECT statement:
		 * ALL|DISTINCT|DISTINCTROW|HIGH_PRIORITY|STRAIGHT_JOIN|SQL_SMALL_RESULT|SQL_BIG_RESULT|SQL_BUFFER_RESULT|SQL_NO_CACHE|SQL_CALC_FOUND_ROWS
		 *
		 * @return null|string
		 */
		public function getAttributes() {
			
			$sql = null;
			
			if ( empty( $this->attributes ) ) {
				return $sql;
			}
			
			$sql = '';
			
			foreach ( $this->attributes as $attribute ) {
				
				if ( 1 === preg_match( '/ALL|DISTINCT|DISTINCTROW/i', $attribute ) ) {
					
					$sql .= sprintf( " %s \n", $attribute );
					
				} else {
					$sql .= sprintf( "%s\n", $attribute );
				}
			}
			
			return $sql;
		}
		
		/**
		 * Add the Group By clause
		 *
		 * @param array $clause
		 */
		public function addGroupBy( $clause ) {
			
			$group_by_clause       = $this->sanitizeData( $clause );
			$this->group_clauses[] = $group_by_clause;
		}
		
		/**
		 * Generate the GROUP BY portion of the SELECT statement
		 *
		 * @return null|string
		 */
		private function getGroupBy() {
			
			$utils    = Utilities::get_instance();
			$group_by = null;
			
			if ( empty( $this->group_clauses ) ) {
				$utils->log( "No GROUP BY clauses found" );
				
				return null;
			}
			
			// Don't expect there to be more than one!
			if ( count( $this->group_clauses ) > 1 ) {
				$utils->log( "Error: Too many group by clauses" );
				
				return $group_by;
			}
			
			// Process all GROUP BY clauses in the SELECT statement
			foreach ( $this->group_clauses as $clause ) {
				
				if ( ! empty( $clause['table_alias'] ) && ! empty( $clause['column'] ) ) {
					$group_by = sprintf( "GROUP BY %s.%s\n", $clause['table_alias'], $clause['column'] );
				} else if ( isset( $clause['column'] ) ) {
					$group_by = sprintf( "GROUP BY %s\n", $clause['column'] );
				}
			}
			
			return $group_by;
		}
		
		/**
		 * Generate the HAVING portion of the SELECT statement
		 *
		 * @return null|string
		 */
		private function getHaving() {
			
			$having = null;
			
			return $having;
		}
		
		/**
		 * Generate the FROM portion of the SELECT statement
		 *
		 * @return string
		 */
		private function getFrom() {
			
			$utils          = Utilities::get_instance();
			$sprintf_format = "FROM %1\$s\n";
			
			if ( isset( $this->from['alias'] ) && ! empty( $this->from['alias'] ) ) {
				$sprintf_format = "FROM %1\$s AS %2\$s\n";
			}
			
			$utils->log( "Adding FROM clause" );
			$sql = sprintf(
				$sprintf_format,
				$this->from['name'],
				isset( $this->from['alias'] ) && ! empty( $this->from['alias'] ) ? $this->from['alias'] : null );
			
			return $sql;
		}
		
		/**
		 * Generate the SQL statement and return it
		 *
		 * @return string
		 */
		public function getStatement() {
			
			// TODO: Add error checking for the SQL statement
			$utils = Utilities::get_instance();
			
			/**
			 * For instance:
			 *
			 * $this->group_clauses probably needs to have $this->having_clauses contain something
			 *
			 */
			
			if ( empty( $this->from ) ) {
				trigger_error( __( 'No table (name) specified for SELECT statement!', E20R_Directory_For_PMPro::plugin_slug ), E_USER_WARNING );
				
				return null;
			}
			
			$this->sql = sprintf( "SELECT " );
			
			if ( ! empty( $this->attributes ) ) {
				$this->sql .= $this->getAttributes();
			}
			
			if ( empty( $this->columns ) ) {
				
				trigger_error( __( 'No columns specified. Using wild-card (all)...', E20R_Directory_For_PMPro::plugin_slug ), E_USER_NOTICE );
				$this->columns[] = array( 'name' => '*', 'alias' => null );
				
			}
			$this->sql .= $this->getColumns();
			$this->sql .= $this->getFrom();
			
			if ( ! empty( $this->joins ) ) {
				$this->sql .= $this->getJoins();
			}
			
			if ( ! empty( $this->where_clauses ) ) {
				$utils->log( "Adding where clauses" );
				$this->sql .= $this->getWhere();
			}
			
			if ( ! empty( $this->group_clauses ) && empty( $this->having_clauses ) ) {
				trigger_error( __( 'Have \'GROUP BY\' but no \'HAVING\' clauses.', E20R_Directory_For_PMPro::plugin_slug ), E_USER_NOTICE );
			}
			
			if ( ! empty( $this->group_clauses ) ) {
				$this->sql .= $this->getGroupBy();
			}
			
			if ( ! empty( $this->having_clauses ) ) {
				$this->sql .= $this->getHaving();
			}
			
			if ( ! empty( $this->order_by ) ) {
				$this->sql .= $this->getOrderBy();
			}
			
			if ( ! empty( $this->limit ) ) {
				$this->sql .= $this->getLimit();
			}
			
			return $this->sql;
		}
		
		/**
		 * Generate the columns to include in the SELECT statement
		 *
		 * @return string
		 */
		private function getColumns() {
			
			$string        = '';
			$total_columns = count( $this->columns );
			$counter       = 1;
			$utils         = Utilities::get_instance();
			
			$utils->log( "Have {$total_columns} to process" );
			
			/**
			 * Content/format for the Column list
			 *
			 * @var array( 'name' => '' , 'alias' => '', 'prefix' => '' ) $column_info
			 */
			foreach ( $this->columns as $column_info ) {
				
				$column_info    = $this->sanitizeData( $column_info );
				$sprintf_format = "\t%1\$s.%2\$s %3\$s,\n";
				
				// No prefix so skip it
				if ( empty( $column_info['prefix'] ) ) {
					$sprintf_format = "\t%2\$s %3\$s,\n";
				}
				
				$utils->log( "Processing column #{$counter}" );
				
				// No comma (,) for last column
				if ( $counter >= $total_columns ) {
					
					$utils->log( "Removing trailing comma" );
					$sprintf_format = preg_replace( '/,/', '', $sprintf_format );
				}
				
				$string .= sprintf(
					$sprintf_format,
					$column_info['prefix'],
					$column_info['column'],
					( ! empty( $column_info['alias'] ) ? sprintf( 'AS %1$s', esc_sql( $column_info['alias'] ) ) : null )
				);
				
				$counter ++;
			}
			
			return $string;
		}
		
		/**
		 * Does the specified table name exist in the DB
		 *
		 * @param string $table_name
		 *
		 * @return bool
		 */
		private function tableExists( $table_name ) {
			
			global $wpdb;
			
			$tables_found = $wpdb->get_results( "SHOW TABLES LIKE '{$table_name}'" );
			
			Utilities::get_instance()->log( "Found for {$table_name}" );
			
			if ( ! empty( $tables_found ) ) {
				return true;
			}
			
			return false;
		}
		
		/**
		 * Find the name of the table to JOIN on
		 *
		 * @param array      $join_clause
		 * @param int|string $key
		 *
		 * @return bool|string
		 */
		private function returnJoinTable( $join_clause, $key ) {
			
			$utils    = Utilities::get_instance();
			$on_table = ( isset( $join_clause['table_name'] ) && ! empty( $join_clause['table_name'] ) ) ? $join_clause['table_name'] : false;
			
			$utils->log( "Current on_table value: {$on_table}" );
			
			if ( empty( $on_table ) ) {
				$utils->log( "Didn't find a 'table_name' attribute in " . print_r( $join_clause, true ) );
				$on_table = ( ! is_int( $key ) && is_string( $key ) && 'table_name' != $key ) ? $key : false;
			}
			
			if ( empty( $on_table ) ) {
				$utils->log( "Error: The JOIN clause didn't include a 'table_name' attribute for: " . print_r( $join_clause, true ) );
				
				return false;
			}
			
			return $on_table;
		}
		
		/**
		 * Add a join clause if it contains the right settings/information
		 *
		 * @param array $join_clause - array( 'table_name' => array( 'table_alias' => '', 'type' => '', 'on_clause' => '', 'order' => int ) )
		 *
		 * @return bool
		 */
		public function addJoin( $join_clause ) {
			
			$join_clause = $this->sanitizeData( $join_clause );
			
			$required = array(
				'type',
				'table_name',
				'on_clause',
				'order',
			);
			
			$utils      = Utilities::get_instance();
			$join_table = $this->returnJoinTable( $join_clause, null );
			
			if ( false === $this->hasRequiredInfo( $join_clause, $required ) ) {
				
				$utils->log( "Cannot add JOIN clause # {$join_clause['order']}" );
				
				return false;
			}
			
			if ( false === $join_table || false === $this->tableExists( $join_table ) ) {
				$utils->log( "Table {$join_table} not found!" );
				
				return false;
			}
			
			$this->joins[] = $join_clause;
			
			usort( $this->joins, array( $this, 'sortJoin' ) );
		}
		
		/**
		 * Order the clauses in the list
		 *
		 * @param array $a_value
		 * @param array $b_value
		 *
		 * @return int
		 */
		private function sortJoin( $a_value, $b_value ) {
			
			// $a_value = array_pop( $a );
			// $b_value = array_pop( $b );
			
			if ( $a_value['order'] == $b_value['order'] ) {
				return 0;
			}
			
			return ( $a_value['order'] > $b_value['order'] ) ? + 1 : - 1;
		}
		
		/**
		 * Generate any JOIN statements if needed
		 *
		 * @return string|null
		 */
		private function getJoins() {
			
			$joins = null;
			$utils = Utilities::get_instance();
			
			if ( empty( $this->joins ) ) {
				$utils->log( "No JOIN statements found!" );
				
				return $joins;
			}
			
			$utils->log( "Adding all JOIN statements to SQL" );
			$joins = '';
			
			// Generate JOIN statements: <type> JOIN <table> AS <table_alias> ON <on_clause> WHERE <on_where>
			foreach ( $this->joins as $join_key => $join_clause ) {
				
				$on_table = $this->returnJoinTable( $join_clause, $join_key );
				
				if ( false === $on_table ) {
					$utils->log( "Error: The JOIN clause didn't include a table_name for: " . print_r( $join_clause, true ) );
					
					return $joins;
				}
				
				$utils->log( "Adding JOIN for {$on_table}" );
				
				$joins .= sprintf( "\t %1\$s JOIN %2\$s", $join_clause['type'], $on_table );
				
				if ( ! empty( $join_clause['table_alias'] ) ) {
					$joins .= sprintf( ' AS %s', $join_clause['table_alias'] );
				}
				
				$joins .= sprintf( ' ON %s', wp_unslash( $join_clause['on_clause'] ) );
				
				if ( ! empty( $join_clause['on_where'] ) ) {
					$joins .= sprintf( " WHERE ( %s )", $join_clause['on_where'] );
				}
				
				$joins .= sprintf( "\n" );
			}
			
			return $joins;
		}
		
		
		/**
		 * Escape for DB
		 *
		 * @param string|array $to_sanitize
		 *
		 * @return array|string
		 */
		private function sanitizeData( $to_sanitize ) {
			
			global $wpdb;
			
			if ( ! is_array( $to_sanitize ) ) {
				$to_sanitize = array( $to_sanitize );
			}
			
			// Iterate through the list of data and escape it
			return array_map( array( $wpdb, '_escape' ), $to_sanitize );
		}
		
		/**
		 * Generate the 'WHERE' clause(s) for the SELECT statement
		 *
		 * @return null|string
		 */
		private function getWhere() {
			
			$where = null;
			$utils = Utilities::get_instance();
			
			if ( empty( $this->where_clauses ) ) {
				$utils->log( "No 'WHERE' clauses defined" );
				
				return $where;
			}
			
			$where     = sprintf( " WHERE 1=1 \n" );
			$sub_where = null;
			
			ksort( $this->where_clauses );
			
			foreach ( $this->where_clauses as $clause_key => $clause ) {
				
				$utils->log( "Processing WHERE clause #{$clause_key}" );
				
				if ( isset( $clause['multi_clause'] ) && true === (bool) $clause['multi_clause'] ) {
					
					$utils->log( "Processing a multi_clause statement" );
					
					// Skip 'operator' argument since it's handled here for multi_clause === true
					$where              .= sprintf( "\t%s ( \n", $clause['operator'] );
					$clause['operator'] = null;
				}
				
				$where .= $this->processWhere( $clause );
				
				// Loop through any sub-clauses to append at the end of this clause
				if ( isset( $clause['sub_clause'] ) && ! empty( $clause['sub_clause'] ) ) {
					
					$sub_where = '';
					
					foreach ( $clause['sub_clause'] as $sub_clause ) {
						$utils->log( "Sub clause... " );
						$where .= $this->processWhere( $sub_clause, false );
					}
				}
				
				if ( isset( $clause['multi_clause'] ) && true === (bool) $clause['multi_clause'] ) {
					$where .= sprintf( "\t) \n" );
				}
				
			}
			
			return $where;
		}
		
		/**
		 * Generate the actual SQL statement for the WHERE statement
		 *
		 * @param array $clause
		 * @param bool  $is_subclause
		 *
		 * @return string
		 */
		private function processWhere( $clause, $is_subclause = false ) {
			
			$utils = Utilities::get_instance();
			$where = '';
			
			global $wpdb;
			
			// Format: Operator Column Comparison Value (i.e. AND mu.user_id = 10 )
			$sprintf_format = "\t%1\$s ( %2\$s %3\$s %4\$s ) ";
			
			if ( true === $is_subclause ) {
				$sprintf_format = "\t( %2\$s %3\$s %4\$s ) ";
			}
			
			$utils->log( "This is a '{$clause['type']}' WHERE clause. It uses .." );
			
			switch ( $clause['type'] ) {
				
				case 'like':
					
					$where .= $this->likeClause( $clause, $is_subclause, null );
					break;
				
				case 'in':
					
					$where .= $this->inClause( $clause, $is_subclause, null );
					break;
				
				default:
					
					// Assume string type if not set...
					$value = (
					isset( $clause['variable_type'] ) && 'numeric' === $clause['variable_type'] ?
						$wpdb->_escape( $clause['value'] ) :
						'\'' . $wpdb->_escape( $clause['value'] ) . '\''
					);
					
					$utils->log( "Using value: {$value}" );
					
					$where .= sprintf(
						"{$sprintf_format} \n",
						( false === $is_subclause ? $clause['operator'] : null ),
						(
						! empty( $clause['prefix'] ) ?
							sprintf( '%s.%s', $clause['prefix'], $clause['column'] ) :
							sprintf( '%s', $clause['column'] )
						),
						$clause['comparison'],
						$value
					);
					
					$where = preg_replace( '/\ \'NULL\'/i', ' NULL', $where );
			}
			
			return $where;
		}
		
		/**
		 * Generates a 'LIKE' based WHERE clause
		 *
		 * @param array  $clause
		 * @param bool   $is_subclause
		 * @param string $sprintf_format
		 *
		 * @return string
		 */
		private function likeClause( $clause, $is_subclause = false, $sprintf_format = null ) {
			
			$string = '';
			global $wpdb;
			$utils = Utilities::get_instance();
			
			$utils->log( "Processing like clause (is it a sub clause? {$is_subclause}): " . print_r( $clause, true ) );
			
			// The value contains one or more wildcards
			if ( 1 === preg_match( '/' . preg_quote( '%' ) . '/', $clause['value'] ) ) {
				
				$utils->log( "Value contains wildcard, so escape it!" );
				$clause['value'] = preg_replace( '/\%/', '%%', $clause['value'] );
			}
			
			$esc_like = $wpdb->esc_like( $clause['value'] );
			
			if ( true === (bool) $clause['fuzzy_search'] ) {
				$utils->log( "Using 'fuzzy search' (wildcard search) " );
				$esc_like = "%{$esc_like}%";
			} else {
				$esc_like = "{$esc_like}";
			}
			
			$tabs = "\t";
			
			if ( $is_subclause ) {
				$tabs = "\t\t";
			}
			
			if ( ! empty( $clause['prefix'] ) ) {
				// See above for wrapping LIKE value in '' (single quotes)
				$string .= sprintf(
					( ! empty( $sprintf_format ) ? $sprintf_format : "{$tabs}%1\$s ( %2\$s.%3\$s LIKE %4\$s ) \n" ),
					( false === $is_subclause ? $clause['operator'] : null ),
					$clause['prefix'],
					$clause['column'],
					( 'string' == $clause['variable_type'] ? "'" . $esc_like . "'" : $esc_like )
				);
			} else {
				// See above for wrapping LIKE value in ''s
				$string .= sprintf(
					( ! empty( $sprintf_format ) ? $sprintf_format : "{$tabs}%1\$s ( %2\$s LIKE %3\$s ) \n" ),
					( false === $is_subclause ? $clause['operator'] : null ),
					$clause['column'], $esc_like );
			}
			
			return $string;
		}
		
		/**
		 * Generates an IN based WHERE clause
		 *
		 * @param array       $clause
		 * @param bool        $is_subclause
		 * @param string|null $sprintf_format
		 *
		 * @return string
		 */
		private function inClause( $clause, $is_subclause = false, $sprintf_format = null ) {
			
			if ( ! is_array( $clause['value'] ) ) {
				$clause['value'] = array( $clause['value'] );
			}
			
			$utils = Utilities::get_instance();
			$utils->log( "Processing like clause (is it a sub clause? {$is_subclause}): " . print_r( $clause, true ) );
			
			$string = '';
			$values = null;
			
			switch ( $clause['variable_type'] ) {
				case 'numeric':
					$values = implode( ', ', $clause['value'] );
					break;
				default:
					$values = "'" . implode( "', '", $clause['value'] ) . "'";
					break;
			}
			
			$tabs = "\t";
			
			if ( $is_subclause ) {
				$tabs = "\t\t";
			}
			$utils->log( "With or without prefix: {$clause['prefix']}" );
			
			if ( ! empty( $clause['prefix'] ) ) {
				
				$string .= sprintf(
					( ! empty( $sprintf_format ) ? $sprintf_format : "{$tabs}%1\$s ( %2\$s.%3\$s %4\$s IN (%5\$s) )\n" ),
					( false === $is_subclause ? $clause['operator'] : null ),
					$clause['prefix'],
					$clause['column'],
					( isset( $clause['invert'] ) && true === $clause['invert'] ? 'NOT' : null ),
					$values
				);
			} else {
				$string .= sprintf(
					( ! empty( $sprintf_format ) ? $sprintf_format : "{$tabs}%1\$s ( %2\$s %3\$s IN (%4\$s) )\n" ),
					( false === $is_subclause ? $clause['operator'] : null ),
					$clause['column'],
					( isset( $clause['invert'] ) && true === $clause['invert'] ? 'NOT' : null ),
					$values
				);
			}
			
			$utils->log( "Returning: {$string}" );
			
			return $string;
		}
		
		/**
		 * Add the Sort Order instructions for the SQL statement
		 *
		 * @param array  $clauses
		 * @param string $order
		 *
		 * @uses $wpdb
		 */
		public function addOrderBy( $clauses, $order = 'ASC' ) {
			
			global $wpdb;
			
			$utils = Utilities::get_instance();
			$order = $wpdb->_escape( $order );
			
			if ( $this->order_by_order != $order ) {
				$this->order_by_order = $order;
			}
			
			if ( isset( $clauses['column'] ) ) {
				$clauses = array( $clauses );
			}
			
			$required = array(
				'column',
			);
			
			foreach ( $clauses as $key => $clause ) {
				
				$clause = $this->sanitizeData( $clause );
				
				if ( false === $this->hasRequiredInfo( $clause, $required ) ) {
					$utils->log( "Error: Incorrect config for the ORDER BY clause! Will not add #{$key}" );
					continue;
				}
				
				$utils->log( "ORDER BY clause: " . print_r( $clause, true ) );
				$this->order_by[] = $clause;
			}
		}
		
		/**
		 * Add SQL LIMIT clause to SELECT statement
		 *
		 * @param array $clause
		 *
		 * @return bool
		 */
		public function addLimit( $clause ) {
			
			$utils = Utilities::get_instance();
			
			if ( empty( $clause ) ) {
				return false;
			}
			
			$required = array(
				'start',
				'end',
			);
			
			if ( isset( $clause['pagination'] ) && false === $clause['pagination'] ) {
				$utils->log( "Not adding pagination clause" );
				
				return false;
			}
			
			if ( false === $this->hasRequiredInfo( $clause, $required ) ) {
				$utils->log( "Cannot add JOIN clause..." );
				
				return false;
			}
			
			if ( 0 == $clause['start'] && 0 == $clause['end'] ) {
				$utils->log( "Not a valid limit clause." );
				
				return false;
			}
			
			$this->limit[] = $clause;
		}
		
		/**
		 * Generates the ORDER BY SQL clause
		 *
		 * @return null|string
		 */
		private function getOrderBy() {
			
			$orderby = null;
			
			if ( empty( $this->order_by ) ) {
				return $orderby;
			}
			
			if ( empty( $this->order_by_order ) ) {
				$this->order_by_order = 'ASC';
			}
			
			$orderby  = ' ORDER BY ';
			$is_multi = ( count( $this->order_by ) > 1 );
			
			foreach ( $this->order_by as $ob_clause ) {
				
				$orderby = $this->selectOrderBy( $orderby, $ob_clause, $is_multi );
			}
			
			$orderby .= sprintf( " %s \n", $this->order_by_order );
			
			return $orderby;
		}
		
		/**
		 * Pick the string format to use for the ORDER BY statement (based on clause)
		 *
		 * @param string $orderby_string
		 * @param array  $clause
		 *
		 * @return string
		 */
		private function selectOrderBy( $orderby_string, $clause, $is_multi ) {
			
			if ( empty( $clause['prefix'] ) ) {
				$orderby_string .= sprintf( '%1$s', $clause['column'] );
			} else {
				$orderby_string .= sprintf( '%1$s.%2$s', $clause['prefix'], $clause['column'] );
			}
			
			return ( $is_multi ) ? $orderby_string . ', ' : $orderby_string;
		}
		
		/**
		 * Generates the LIMIT SQL clause
		 *
		 * @return null|string
		 */
		private function getLimit() {
			
			$limit = null;
			
			if ( empty( $this->limit ) ) {
				return $limit;
			}
			
			foreach ( $this->limit as $limit_clause ) {
				
				$pagination = (bool) ( isset( $limit_clause['pagination'] ) ? $limit_clause['pagination'] : false );
				$results    = isset( $limit_clause['results'] ) && ! empty( $limit_clause['results'] ) ? $limit_clause['results'] : null;
				
				// Using pagination
				if ( true === $pagination ) {
					
					$start = $limit_clause['start'];
					$end   = isset( $limit_clause['end'] ) ? $limit_clause['end'] : null;
					
					if ( is_null( $end ) && ! empty( $results ) ) {
						$end = $results;
					}
					
					$limit = sprintf( " LIMIT %d, %d \n", $start, $end );
				}
			}
			
			return $limit;
		}
	}
}