<?php

namespace tables\users;

use models\config;
use tables\_default;
use tables\statuses\employer;
use tables\users;

class resetPassword extends _default
{
    public $ajaxModel = 'users\resetPassword';

    public $primaryKey = 'u.id';

    public $fields = [
        'firstName' => [
            'select' => 'u.firstName',
            'display' => 'First Name',
        ],
        'lastName' => [
            'select' => 'u.lastName',
            'display' => 'Last Name',
        ],
        'username' => [
            'select' => 'u.username',
            'display' => 'Username',
        ],
        'email' => [
            'select' => 'u.email',
            'display' => 'Email',
        ],
        'resetPassword' => [
            'select' => 'u.id',
            'display' => 'Reset Password',
            'ignoreSearch' => TRUE
        ]
    ];

    public $mainField = 'u.id';

    const STATUS_EMPLOYER_SELDAT = 'SD';

    const STATUS_EMPLOYER_CLIENT = 'CL';

    const DEVELOPER_LEVEL_CONSTANT = 1;

    static $seldatStatusID;

    /*
    ****************************************************************************
    */

    function __construct($app)
    {
        $statuses = new employer($app);
        self::$seldatStatusID =
            $statuses->getStatusID(self::STATUS_EMPLOYER_SELDAT);

        $this->where = 'u.active AND u.employer = ' . self::$seldatStatusID;

        parent::__construct($app);
    }
    
    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return $userDB.'.info u
               LEFT JOIN statuses s ON s.id = u.employer
               LEFT JOIN users_access a ON u.id = a.userID
               LEFT JOIN user_levels l ON l.id = a.levelID';
    }

    /*
    ****************************************************************************
    */

    public function resetPassword($app)
    {
        $userInfo = $this->getAllUserReset($app);

        $results = $this->processResetPassword($userInfo, $app);

        $return = count($results) > 1 ? count($results)
            . ' users was reset passwords.': array_shift($results);

        return $return;
    }

    /*
    ****************************************************************************
    */

    public function getAllUserReset($app)
    {
        $rand = rand(1, 1000);
        $params[] = self::$seldatStatusID;
        $where = '';

        if (isset($app->post['userID'])) {
            $where = 'AND id = ?';
            $params[] = $app->post['userID'];
        }

        $userDB = $this->app->getDBName('users');

        $sql = 'SELECT  id,
                        id AS userID,
                        username,
                        CONCAT_WS(" ", firstName, lastName) AS fullName,
                        email,
                        LEFT (
                            UCASE(MD5(RAND(' . $rand . '))),
                            8
                        ) AS newPassword
                FROM    ' . $userDB . '.info
                WHERE	active
                AND     employer = ?
                ' . $where;

        return $this->app->queryResults($sql, $params);
    }

    /*
    ****************************************************************************
    */

    public function processResetPassword($userInfo, $app)
    {
        $users = new users($app);
        $return = [];
        $row = 1;
        $isSingleReset = isset($app->post['userID']) && count($userInfo) == 1;
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->SetCellValue('A1', 'User');
        $objPHPExcel->getActiveSheet()->SetCellValue('B1', 'Username');
        $objPHPExcel->getActiveSheet()->SetCellValue('C1', 'Email');
        $objPHPExcel->getActiveSheet()->SetCellValue('D1', 'Password');

        $adminInfo = $users->getUser(\access::getUserID());

        $app->beginTransaction();

        foreach ($userInfo as $result) {
            $row++;

            $subject = 'Password Reset';
            $body = 'Hello ' . $result['fullName'] . ', your new AWMS password was'
                .' reset.<br> Make sure to  reset your password once you have '
                .'successfully logged in. <br>';

            if (isset($app->post['inputContent'])) {
                $body .= getDefault($app->post['inputContent'], '') . '<br>';
            }

            $body .= '<br>New AWMS credentials: <br>Username: '
                . $result['username'] . '<br>Password: ' . $result['newPassword'];

            $this->updateNewPassword($result, $app);

            $objPHPExcel->getActiveSheet()->SetCellValue('A' . $row,
                $result['fullName']);
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $row,
                $result['username']);
            $objPHPExcel->getActiveSheet()->SetCellValue('C' . $row,
                $result['email']);
            $objPHPExcel->getActiveSheet()->SetCellValue('D' . $row,
                $result['newPassword']);

            $return[] = $result['fullName'] . ' new AWMS password was sent to '
                . $result['email'];

            $params = [
                'recipient' => $result['email'],
                'subject' => $subject,
                'body' => $body
            ];

            if ($isSingleReset) {
                $params['addReplyTo'] = [
                    'email' => $adminInfo['email'],
                    'fullName' => $adminInfo['fullName']
                ];
            }

            // Send mail
            \PHPMailer\send::mail($params);
        }

        $app->commit();

        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

        $prefix = 'Reset_Password_';
        $uploadPath = \models\directories::getDir('uploads', 'resetPassword');
        $numberDate = config::getDateTime('currentTime');
        $objWriter->save($uploadPath . '/' . $prefix . $numberDate . '.xlsx');

        return $return;

    }

    /*
    ****************************************************************************
    */

    public function updateNewPassword($data, $app)
    {
        $userDB = $app->getDBName('users');

        $hash = md5($data['newPassword']);

        $sql = 'UPDATE  ' . $userDB . '. info
                SET     password = ?
                WHERE   id = ?';

        $this->app->runQuery($sql, [
            $hash,
            $data['userID']
        ]);
    }

    /*
    ****************************************************************************
    */
}