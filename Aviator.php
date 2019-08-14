<?php

/*
 * Version  :   3.1
 * Updated  :   27/07/2019 21:14pm
 */

/*
 * The MIT License
 *
 * Copyright 2018 Aviator.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/*
 *
 * Add configuration file
 */
define("POST_INPUT_TYPE", "POST");
define("GET_INPUT_TYPE", "GET");

/*
 * DB CONNECTION PROPERTIES
 */
define("servername", "localhost"); // server
define("username", "root"); //server username
define("password", ""); //server password
define("dbname", "DBNAME_HERE"); //database name

abstract class Aviator {

    public $conn;

    function __construct() {
        $this->conn = self::Av_DbConecti();
    }

    static function Av_getInstance() {
        return self::class;
    }

    protected static function Av_DbConecti() {

        $connection = mysqli_connect(servername, username, password, dbname);
        if (!$connection) {
            die(json_encode(self::Av_ResponseText(TRUE, "FAILED TO CONNECT")));
        }
        return $connection;
    }

    public static function Av_GetMultiRowsData($sql) {
        $query = mysqli_query(self::Av_DbConecti(), $sql);
        $result = array();
        while ($row = mysqli_fetch_assoc($query)) {
            array_push($result, $row);
        }
        if (sizeof($result, 0) > 0) {
            return $result;
        } else {
            return NULL;
        }
    }

    public static function Av_GetMultiRowsDataSize($sql) {

        $query = mysqli_query(self::Av_DbConecti(), $sql);
        $result = array();
        while ($row = mysqli_fetch_assoc($query)) {
            array_push($result, $row);
        }
        if (sizeof($result, 0) > 0) {
            return sizeof($result, 0);
        } else {
            return 0;
        }
    }

    public static function Av_GetRowDataDB($sql) {

        $query = mysqli_query(self::Av_DbConecti(), $sql);

        if (!$query) {
            return null;
        }

        ($row = mysqli_fetch_assoc($query));
        if (is_array($row) && (sizeof($row, 0) > 0)) {
            return $row;
        } else {
            return NULL;
        }
    }

    /**
     * Execute sql statement
     * @param $sql
     * @param $message default message for successful operation
     * @return jsonobject
     */
    public static function Av_ExecSql($sql, $message) {
        $query = mysqli_query(self::Av_DbConecti(), $sql);
        if ($query) {
            return self::Av_ResponseText(FALSE, $message);
        } else {
            return self::Av_ResponseText(TRUE, "Failed,try again");
        }
    }

    /**
     * get input value
     * @param $param
     * @return string
     */
    public static function getParam($param, $isSanitize = true, $type = "POST") {
        switch ($type) {
            case "POST":
                if ($isSanitize) {
                    return self::sanitize($_POST["$param"]);
                }
                return $_POST["$param"];

            case "GET":
                if ($isSanitize) {
                    return self::sanitize($_GET["$param"]);
                }
                return $_GET["$param"];

            default:
                return self::sanitize($_POST["$param"]);
        }
    }

    /**
     * Av_sanitizes the data input values
     * @param $data
     * @return string
     */
    private static function Av_sanitize($data) {
        $data = filter_var($data, FILTER_Av_sanitize_STRING);
        $data = trim($data);
        $data = stripslashes($data);
//        $data = htmlspecialchars($data);
        $data = mysqli_real_escape_string(self::Av_DbConecti(), $data);
        return $data;
    }

    public static function Av_CheckParamsIfSet($arrayParams) {
        $response = array();
        foreach ($arrayParams as $value) {
            if (!isset($_POST["$value"])) {
                array_push($response, $value);
            }
        }

        if (sizeof($response, 0) > 0) {
            return $response;
        }
        return null;
    }

    public static function Av_SetParamIfNot($param, $value) {
        if (isset($_POST["$param"])) {
            return $_POST["$param"];
        }
        return $value;
    }

    public static function Av_ResponseText($boolean, $message) {
        $result = array();
        $result["error"] = $boolean;
        $result["message"] = $message;

        return $result;
    }

    public static function Av_WriteJSON($array) {
        return json_encode($array);
    }

    public static function Av_dieJSON($array) {
        die(self::Av_WriteJSON($array));
    }

    public static function Av_millitime() {
        $microtime = microtime();
        $comps = explode(' ', $microtime);

        // Note: Using a string here to prevent loss of precision
        // in case of "overflow" (PHP converts it to a double)
        return sprintf('%d%03d', $comps[1], $comps[0] * 1000);
    }

    public static function Av_UploadFile($fileNameParam, $path) {  //$path ends with {/} eg: disorder/malaria/
        $file_path = $path; //("images/")
        if (!file_exists($file_path)) {
            mkdir($file_path, 0777);
        }
        $file_name = basename($_FILES["$fileNameParam"]['name']);
        $file_path = $file_path . $file_name;
        if (file_exists($file_path)) {
            $file_name = self::millitime() . $file_name;
            $file_path = $path . $file_name;
        }

        if (move_uploaded_file($_FILES["$fileNameParam"]['tmp_name'], $file_path)) {
            $result = array();
            $result["error"] = FALSE;
            $result["filename"] = $file_name;
            $result["message"] = "Uploaded";
            return $result;
        } else {
            return self::Av_ResponseText(TRUE, "File upload failed");
        }
    }

    public static function Av_GetPATH($parentfolder) {
        $arrayParams = ["name"];
        $res = self::Av_CheckParamsIfSet($arrayParams);
        if ($res != null) {
            return self::Av_ResponseText(true, "Cant process incomplete form data");
        }

        $name = $_POST["name"];
        $path = "$parentfolder/$name/";
        return $path;
    }

    public static function Av_afterParamsCheck($params) {
        if (self::Av_CheckParamsIfSet($params) != null) {
            die(self::Av_WriteJSON(self::Av_ResponseText(TRUE, "Incomplete request")));
        }
    }

    /*
     * writes data to a given file
     */

    public static function Av_WriteFile($file, $filedata) {
        $filename = $file;

        if (!file_exists($filename)) {
            fopen($filename, 'a');
        }
        if (is_writable($filename)) {
            if (!$handle = fopen($filename, 'a')) {
                return self::Av_ResponseText(true, "Cannot open file");
            }

            if (fwrite($handle, $filedata) === FALSE) {
                return self::Av_ResponseText(true, "Cannot write to file");
            }

            fclose($handle);
            return self::Av_ResponseText(false, "File contents written");
        } else {
            return self::Av_ResponseText(true, "File not writable");
        }
    }

    /*
     *
     */

    public static function Av_GetRandomSeed() {
        return rand(121, 4544222) . rand(54, 233625) . rand(045, 875441) . rand(545, 1313113) . rand(1254, 5454) . rand(1212, 45484) . rand(121, 4544222) . rand(54, 233625);
    }

    /*
     *
     */

    public static function Av_Random_password_generate($length) {

        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-=+?";

        $password = substr(str_shuffle($chars), 0, $length);

        return $password;
    }

    /*
     *
     */

    public static function GetRandomKey($length) {

        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

        $password = substr(str_shuffle($chars), 0, $length);

        return $password;
    }

    /*
     * Returns null if no param set
     */

    public static function Av_getParamIfExists($param, $isCookie = false, $isSession = false) {

        if ($isCookie) {
            if (isset($_COOKIE["$param"])) {
                return self::Av_sanitize($_COOKIE["$param"]);
            }
            return null;
        }

        if ($isSession) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            if (isset($_SESSION["$param"])) {
                return $_SESSION["$param"];
            }
            return null;
        }

        if (isset($_POST)) {
            if (isset($_POST["$param"])) {
                return self::Av_sanitize($_POST["$param"]);
            }
        }

        if (isset($_GET)) {
            if (isset($_GET["$param"])) {
                return self::Av_sanitize($_GET["$param"]);
            }
        }

        return null;
    }

    /*
     *
     */

    public static function Av_FormatDate($date, $format) {
        return (date_create($date))->format($format);
    }

    /* :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: */
    /* ::                                                                         : */
    /* ::  This routine calculates the distance between two points (given the     : */
    /* ::  latitude/longitude of those points). It is being used to calculate     : */
    /* ::  the distance between two locations using GeoDataSource(TM) Products    : */
    /* ::                                                                         : */
    /* ::  Definitions:                                                           : */
    /* ::    South latitudes are negative, east longitudes are positive           : */
    /* ::                                                                         : */
    /* ::  Passed to function:                                                    : */
    /* ::    lat1, lon1 = Latitude and Longitude of point 1 (in decimal degrees)  : */
    /* ::    lat2, lon2 = Latitude and Longitude of point 2 (in decimal degrees)  : */
    /* ::    unit = the unit you desire for results                               : */
    /* ::           where: 'M' is statute miles (default)                         : */
    /* ::                  'K' is kilometers                                      : */
    /* ::                  'N' is nautical miles                                  : */
    /* ::  Worldwide cities and other features databases with latitude longitude  : */
    /* ::  are available at https://www.geodatasource.com                          : */
    /* ::                                                                         : */
    /* ::  For enquiries, please contact sales@geodatasource.com                  : */
    /* ::                                                                         : */
    /* ::  Official Web site: https://www.geodatasource.com                        : */
    /* ::                                                                         : */
    /* ::         GeoDataSource.com (C) All Rights Reserved 2018                  : */
    /* ::                                                                         : */
    /* :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: */

    public static function Av_DistanceBetweenLatLong($lat1, $lon1, $lat2, $lon2, $unit) {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        } else {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $unit = strtoupper($unit);

            if ($unit == "K") {
                return ($miles * 1.609344);
            } else if ($unit == "N") {
                return ($miles * 0.8684);
            } else {
                return $miles;
            }
        }
    }

    /*
     * Get to get Distance between two points using LAT LANG
     *
     * $point1 = array('lat' => 40.770623, 'long' => -73.964367);
      $point2 = array('lat' => 40.758224, 'long' => -73.917404);
      $distance = getDistanceBetweenPointsNew($point1['lat'], $point1['long'], $point2['lat'], $point2['long']);

      //    The example returns the following:
      //
      //    miles: 2.6025
      //    feet: 13,741.4350
      //    yards: 4,580.4783
      //    kilometers: 4.1884
      //    meters: 4,188.3894
     */

    public static function Av_getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2, $unit = 'M') {
        $theta = $longitude1 - $longitude2;
        $miles = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;

        $feet = $miles * 5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        switch ($unit) {
            case 'M'://Miles
                return round($miles, 2);
            case 'F'://Feet
                return round($feet, 2);
            case 'Y'://Yards
                return round($yards, 2);
            case 'K'://Kilometers
                return round($kilometers, 2);
            case 'Mt'://Meters
                return round($meters, 2);
            default :
                return 0;
        }
        //return compact('miles', 'feet', 'yards', 'kilometers', 'meters');
    }

}
