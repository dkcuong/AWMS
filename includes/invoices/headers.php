<?php

namespace invoices;

class headers
{

    /*
    ****************************************************************************
    */

    function __construct($app)
    {
        $this->app = $app;
    }

    /*
    ****************************************************************************
    */

    function updateInvoicePayment($data)
    {
        $action = getDefault($data['action']);
        $date = getDefault($data['date'], '0000-00-00 00:00:00');
        $type = getDefault($data['type'], NULL);
        // reference does not accept NULL values
        $reference = getDefault($data['reference'], '');
        $invoice = getDefault($data['invoice']);

        $errors = [];

        if (! in_array($action, ['makePayment', 'cancelPayment'])) {
            $errors[] = 'Unrecognized or empty action';
        }

        if (! $invoice) {
            $errors[] = 'Invoice Number is a mandatoty value';
        }

        if ($action == 'makePayment') {
            if (! $date) {
                $errors[] = 'Paid Date is a mandatoty input';
            }

            if (! $reference) {
                $errors[] = 'Paid Reference is a mandatoty value';
            }
        }

        $userName = \access::getUserInfoValue('username');

        if (! $userName) {
            $errors[] = 'Unable to obtain current User Name';
        }

        if ($errors) {
            return [
                'errors' => $errors,
                'reference' => $reference,
            ];
        }

        $status = $action == 'makePayment' ? 1 : 0;
        $invoiceStatus = $action == 'makePayment' ? 'p' : 'i';

        $sql = 'UPDATE    invoice_hdr
                SET       inv_sts = ?,
                          inv_paid_sts = ' . $status . ',
                          inv_paid_dt = ?,
                          inv_paid_typ = ?,
                          inv_paid_ref = ?,
                          update_by = ?,
                          sts = "u"
                WHERE     inv_num = ?';

        $this->app->runQuery($sql, [
            $invoiceStatus,
            $date,
            $type,
            $reference,
            $userName,
            $invoice,
        ]);

        return [
            'errors' => $errors,
            'reference' => $reference,
        ];
    }

    /*
    ****************************************************************************
    */

    function getByInvoiceNumber($invoiceNumber)
    {
        if (! $invoiceNumber) {
            return [];
        }

        $sql = 'SELECT    inv_id
                FROM      invoice_hdr
                WHERE     inv_num = ?';

        $result = $this->app->queryResult($sql, [$invoiceNumber]);

        return $result['inv_id'];
    }

    /*
    ****************************************************************************
    */

    function get($invoiceNo)
    {
        $sql = 'SELECT   cust_id,
                         vendorName,
                         DATE(inv_dt) AS inv_dt,
                         cust_ref,
                         CONCAT_WS(" ", bill_to_add1, bill_to_add2) AS bill_to_add,
                         bill_to_state,
                         bill_to_city,
                         bill_to_cnty,
                         bill_to_zip,
                         net_terms
                FROM     invoice_hdr h
                JOIN     vendors v ON v.id = h.cust_id
                WHERE    inv_num = ?';

        $result = $this->app->queryResult($sql, [$invoiceNo]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getNextInvoiceNumber()
    {
        $sql = 'SELECT    inv_num
                FROM      invoice_hdr
                ORDER BY  inv_num DESC
                LIMIT     1';

        $result = $this->app->queryResult($sql);

        return $result['inv_num'] + 1;
    }

    /*
    ****************************************************************************
    */

    function getStatusByNumber($invoiceNo)
    {
        $sql = 'SELECT   inv_sts
                FROM     invoice_hdr
                WHERE    inv_num = ?';

        $result = $this->app->queryResult($sql, [$invoiceNo]);

        return $result['inv_sts'];
    }

    /*
    ****************************************************************************
    */

    function getCancellingData($invoiceNo)
    {
        $sql = 'SELECT   wh_id,
                         cust_id,
                         inv_sts,
                         inv_cur,
                         -1 * inv_amt AS inv_amt,
                         -1 * inv_tax AS inv_tax,
                         cust_ref,
                         net_terms,
                         bill_to_add1,
                         bill_to_add2,
                         bill_to_state,
                         bill_to_city,
                         bill_to_cnty,
                         bill_to_zip,
                         bill_to_contact
                FROM     invoice_hdr
                WHERE    inv_num = ?';

        $result = $this->app->queryResult($sql, [$invoiceNo]);

        return $result;
    }

    /*
    ****************************************************************************
    */

}
