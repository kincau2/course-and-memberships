<?php

class HKOTA_User_Membership {
    public $id;
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
    public function __construct($id) {

        $wc_membership = new WC_Memberships_User_Membership(id);

        $this->id = $id;
        $this->status = $wc_membership->status;
        $this->plan_id = $wc_membership->plan->id;
        $this->start_date = get_post_meta( $this->id, '_start_date', true);
        $this->end_date = get_post_meta( $this->id, '_end_date', true);

    }


}












































































?>
