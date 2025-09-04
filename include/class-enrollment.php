<?php

class Enrollment {
    public $id;
    public $user_id;
    public $course_id;
    public $status; // enrollment status: enrolled, awaiting_approval, pending, waiting_list, rejected, in-hold
    public $certificate_status; // certificate status: issued or not_issue
    public $order_id;
    public $amount;
    public $date_created_gmt;
    public $payment_method;
    public $attendance;
    public $uploads;
    public $survey;
    public $quiz;

    // Constructor to initialize the properties from the database row
    public function __construct($enrollment_id) {
        global $wpdb;

        // Define the table name
        $table = $wpdb->prefix . 'hkota_course_enrollment';

        // Fetch the enrollment record from the database
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE ID = %d LIMIT 1",
            $enrollment_id
        ));

        // If no record is found, throw an error or set a default state
        if (is_null($row)) {
            throw new Exception(__('Enrollment not found', 'textdomain'));
        }

        // Initialize object properties using the fetched data
        $this->id = $row->ID;
        $this->user_id = $row->user_id;
        $this->course_id = $row->course_id;
        $this->status = $row->status;
        $this->certificate_status = $row->certificate_status;
        $this->order_id = $row->order_id;
        $this->amount = $row->amount;
        $this->date_created_gmt = $row->date_created_gmt;
        $this->payment_method = $row->payment_method;
        $this->attendance = maybe_unserialize($row->attendance);
        $this->uploads = maybe_unserialize($row->uploads);
        $this->survey = maybe_unserialize($row->survey);
        $this->quiz = maybe_unserialize($row->quiz);
    }

    public function set($param_name, $value) {

        global $wpdb;

        // Define the table name
        $table = $wpdb->prefix . 'hkota_course_enrollment';

        // Ensure the param_name is a valid column in the table to prevent SQL injection
        $valid_columns = [
            'user_id', 'course_id', 'status', 'certificate_status', 'order_id', 'quiz' ,
            'amount', 'date_created_gmt', 'payment_method', 'attendance' , 'uploads', 'survey'
        ];

        // Check if the provided parameter is a valid column name
        if (!in_array($param_name, $valid_columns)) {
            throw new Exception(__('Invalid parameter name', 'textdomain'));
        }

        // Determine the data type for formatting the query (string, integer, etc.)
        $data_format = '%s'; // Default to string format
        if (in_array($param_name, ['user_id', 'course_id', 'order_id', 'amount'])) {
            $data_format = '%d'; // Integer format for these columns
        }

        // Update the specific column with the provided value
        $updated = $wpdb->update(
            $table,
            [$param_name => maybe_serialize($value)],  // Data to update
            ['ID' => $this->id],      // Where clause
            [$data_format],           // Data format
            ['%d']                    // Where clause format (ID is integer)
        );

        if ($updated === false) {
            throw new Exception(__('Failed to update ' . $param_name, 'textdomain'));
        } else{
          // Dynamically update the object property
          $this->{$param_name} = $value;

          return true;
        }

    }
}












































































?>
