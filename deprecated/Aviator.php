<?php

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

abstract class Aviator {

    public $conn;

    function __construct() {
        $this->conn = $this->DbConecti();
    }

    function DbConecti() {
        $connection = mysqli_connect(servername, username, password, dbname);
        if (!$connection) {
            die(json_encode($this->ResponseText(TRUE, "FAILED TO CONNECT")));
        }
        return $connection;
    }

    function GetMultiRowsData($sql) {
        if (!$this->conn) {
            die(json_encode($this->ResponseText(TRUE, "FAILED TO CONNECT")));
        }

        $query = mysqli_query($this->conn, $sql);
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

    function GetMultiRowsDataSize($sql) {
        if (!$this->conn) {
            die(json_encode($this->ResponseText(TRUE, "FAILED TO CONNECT")));
        }

        $query = mysqli_query($this->conn, $sql);
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

    function GetRowDataDB($sql) {
        if (!$this->conn) {
            die(json_encode($this->ResponseText(TRUE, "FAILED TO CONNECT")));
        }
        $query = mysqli_query($this->conn, $sql);
        ($row = mysqli_fetch_assoc($query));
        if (sizeof($row, 0) > 0) {
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
    function ExecSql($sql, $message) {
        $query = mysqli_query($this->conn, $sql);
        if ($query) {
            return $this->ResponseText(FALSE, $message);
        } else {
            return $this->ResponseText(TRUE, "Failed,try again");
        }
    }

    /**
     * get input value
     * @param $param
     * @return string
     */
    function getParam($param) {
        return $this->sanitize(requestmethod["$param"]);
    }

    /**
     * Sanitizes the data input values
     * @param $data
     * @return string
     */
    private function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        $data = mysqli_real_escape_string($this->conn, $data);
        return $data;
    }

    function CheckParamsIfSet($arrayParams) {
        $response = array();
        foreach ($arrayParams as $value) {
            if (!isset(requestmethod["$value"])) {
                array_push($response, $value);
            }
        }

        if (sizeof($response, 0) > 0) {
            return $response;
        }
        return null;
    }

    function SetParamIfNot($param, $value) {
        if (isset(requestmethod["$param"])) {
            return requestmethod["$param"];
        }
        return $value;
    }

    function ResponseText($boolean, $message) {
        $result = array();
        $result["error"] = $boolean;
        $result["message"] = $message;

        return $result;
        //die(json_encode($result));
    }

    function WriteJSON($array) {
        return json_encode($array);
    }

    static function dieJSON($array) {
        die(self::WriteJSON($array));
    }

    function millitime() {
        $microtime = microtime();
        $comps = explode(' ', $microtime);

        // Note: Using a string here to prevent loss of precision
        // in case of "overflow" (PHP converts it to a double)
        return sprintf('%d%03d', $comps[1], $comps[0] * 1000);
    }

    function UploadFile($fileNameParam, $path) {  //$path ends with {/} eg: disorder/malaria/
        $file_path = $path; //("images/")
        if (!file_exists($file_path)) {
            mkdir($file_path, 0777);
        }
        $file_name = basename($_FILES["$fileNameParam"]['name']);
        $file_path = $file_path . $file_name;
        if (file_exists($file_path)) {
            $file_name = $this->millitime() . $file_name;
            $file_path = $path . $file_name;
        }

        if (move_uploaded_file($_FILES["$fileNameParam"]['tmp_name'], $file_path)) {
            $result = array();
            $result["error"] = FALSE;
            $result["filename"] = $file_name;
            $result["message"] = "Uploaded";
            return $result;
        } else {
            return $this->ResponseText(TRUE, "File upload failed");
        }
    }

    function GetPATH($parentfolder) {
        $arrayParams = ["name"];
        $res = $this->CheckParamsIfSet($arrayParams);
        if ($res != null) {
            die(json_encode($this->ResponseText(true, "Cant process incomplete form data")));
        }

        $name = requestmethod["name"];
        $path = "$parentfolder/$name/";
        return $path;
    }

    function afterParamsCheck($params) {
        if ($this->CheckParamsIfSet($params) != null) {
            die($this->WriteJSON($this->ResponseText(TRUE, "Incomplete request")));
        }
    }

}
