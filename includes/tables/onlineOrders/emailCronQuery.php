<?php

namespace tables\onlineOrders;


class emailCronQuery
{
    private $app;
    private $limitTransfer = 2;
    private $shortNameTransfer = 'TE';
    private $hiddenNameMezzanineAdmin = 'mezzanineAdmin';


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

    public function getNewTransfer()
    {
        $sql = 'SELECT  rd.primeKey AS transferID, rd.data, s.shortName
                FROM    reports_data rd
                JOIN    statuses s ON rd.statusID = s.id
                WHERE   rd.isSent != 1
                AND     s.shortName IN (?)
                ORDER BY rd.id DESC
                LIMIT ' . $this->limitTransfer;

        $results = $this->app->queryResults($sql, [$this->shortNameTransfer]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    public function getMezzanineAdminEmail($vendorID)
    {
        $userDB = $this->app->getDBName('users');

        $sql = 'SELECT u.id,
                       u.email
                FROM   client_users cu
                JOIN   ' . $userDB . '.info u ON u.id = cu.userID
                JOIN   user_groups ug ON ug.userID = u.id
                JOIN   groups gr ON gr.id = ug.groupID
                WHERE  cu.vendorID = ?
                AND    gr.hiddenName = ?
                AND    cu.active
                AND    ug.active
                AND    gr.active
                AND    u.active
                GROUP BY u.id';

        $result = $this->app->queryResults($sql, [
            $vendorID,
            $this->hiddenNameMezzanineAdmin
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    public function getLocationName($locID)
    {
        $sql = 'SELECT  displayName
                FROM    locations
                WHERE id = ?';

        $result = $this->app->queryResult($sql, [$locID]);

        return $result ? $result['displayName'] : NULL;
    }

    /*
    ****************************************************************************
    */

    public function getClienName($vendorID)
    {
        $sql = 'SELECT CONCAT(wh.shortName, "_", v.vendorName) AS clientName
                FROM vendors v
                JOIN warehouses wh ON wh.id = v.warehouseID
                WHERE v.id = ?';

        $result = $this->app->queryResult($sql, [$vendorID]);

        return $result ? $result['clientName'] : NULL;
    }


    /*
    ****************************************************************************
    */

    public function getTransferByID($transferID)
    {
        $sql = 'SELECT *
                FROM transfers
                WHERE id = ?';

        $result = $this->app->queryResult($sql, [$transferID]);

        return $result;

    }

    /*
    ****************************************************************************
    */

    public function createUCC128($cartonID)
    {
        $sql = 'SELECT CONCAT(co.vendorID,
                        b.id,
                        LPAD(ca.uom, 3, 0),
                        LPAD(ca.cartonID, 4, 0))
                        AS ucc128
                FROM    inventory_cartons ca
                JOIN    inventory_batches b ON ca.batchID = b.id
                JOIN    inventory_containers co ON co.recNum = b.recNum
                WHERE   ca.id= ?';

        $result = $this->app->queryResult($sql, [$cartonID]);
        $ucc128 = $result['ucc128'];

        return $ucc128;
    }

    /*
    ****************************************************************************
    */

}