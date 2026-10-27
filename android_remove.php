<?php

class Android_remove{

    private string $success = "OK";
    private string $failed = "ERROR";

    private array $adb_commands = [

        "adb devices",                                         // check device
        "adb shell pm list packages",                          // device all app package name
        "adb shell pm list packages -3",                       // user install app package name
        "adb shell pm uninstall --user 0",                     // remove app via package name
        "adb shell cmd package install-existing --user 0",     // reinstall uninstalled app via package name
        "adb shell pm list packages | grep -i"                 // Find a app  

    ];

    public function run(int $argc, array $argv){

        if($argc < 1){

            echo "Invalid Command\n";
            echo ">  php android_remove.php\n";
            echo ">  php android_remove.php list\n";
            echo ">  php android_remove.php user-list\n";
            echo ">  php android_remove.php find\n";
            echo ">  php android_remove.php remove\n";
            echo ">  php android_remove.php -f <Package Name>\n";
            echo ">  php android_remove.php restore\n";
            exit(1);

        }

        $action = $argv[1] ?? "home";

        switch($action){

            case "home":

                passthru($this->adb_commands[0]);

                break;

            case "list":
                
                echo "===============================================================================================\n";
                passthru($this->adb_commands[1]);
                echo "===============================================================================================\n";

                break;

            case "user-list":

                echo "===============================================================================================\n";
                passthru($this->adb_commands[2]);
                echo "===============================================================================================\n";


                break;

            case "find":

                $this->findApp(true);

                break;

            case "remove":

                $this->app_remove();

                break;

            case "-f":

                if($argc < 3 || $argc > 3){

                    echo "Invalid Command\n";
                    echo "Use : php android_remove.php -f <Package Name>\n";
                    exit(1);

                }

                passthru($this->adb_commands[3] . " " . escapeshellarg($argv[2]), $result_code);

                if($result_code === 0) $this->save_history($argv[2]);

                break;

            case "restore":

                $this->app_restore();

                break;

            default:
            echo "Invalid Command\n";
            echo ">  php android_remove.php\n";
            echo ">  php android_remove.php list\n";
            echo ">  php android_remove.php user-list\n";
            echo ">  php android_remove.php find\n";
            echo ">  php android_remove.php remove\n";
            echo ">  php android_remove.php -f <Package Name>\n";
            echo ">  php android_remove.php restore\n";
            exit(1);

        }

    }

    private function findApp(bool $is_app_list_show = false) : string{

        echo "Enter your app name:\n";
        $app_name = readline(">");

        if(empty($app_name)){

            echo "[{$this->failed}] Empty app name\n";
            exit(1);

        }

        $filter_app_name = preg_replace("/[^a-zA-Z0-9]/", "", $app_name);

        $app_data = shell_exec("{$this->adb_commands[5]} $filter_app_name");

        if(empty($app_data)){

            echo "[{$this->failed}] App not found\n";
            exit(1);

        }

        if($is_app_list_show){

            echo "===============================================================================================\n";
            echo $app_data;
            echo "===============================================================================================\n";

        }

        return $app_data;
    }

    private function app_remove(){

        $app_data = $this->findApp(false);

        $filter_app_data = str_replace("package:", "", $app_data);

        $package_list = preg_split('/\r\n|\r|\n/', trim($filter_app_data));

        $count = 1;

        echo "==================================================================\n";
        foreach ($package_list as $item){

            echo "[{$count}] $item" . PHP_EOL;
            $count++;

        }
        echo "==================================================================\n";
        echo "\n";

        $wrong_count = 1;

        while($wrong_count > 0){

            echo "Enter your choice:\n";
            $app_id = readline(">");

            if(empty($app_id) || !preg_match("/^[0-9]+$/", $app_id)){

                echo "Invalid choice\n";
                echo "\n";

                $wrong_count++;
                continue;

            }else{

                $app_id = (int) $app_id;

                $final_app_package_name = $package_list[$app_id - 1];

                echo "Are you sure you wnant to delete [{$final_app_package_name}] (y/n):\n";
                $choice = (string) readline(">");

                $choice = strtolower($choice);
                if(!preg_match("/^[yn]$/", $choice)){

                    echo "Invalid Choice\n";
                    continue;

                }

                if($choice === "y"){

                    $this->save_history($final_app_package_name);

                    $output = shell_exec("{$this->adb_commands[3]} $final_app_package_name");

                    echo $output . "\n";

                }else if($choice === "n"){

                    echo "[{$this->failed}] App not removed\n";
                    exit(1);

                }
                
                break;

            }

        }
        




    }

    private function app_restore(){

        echo "Enter restore package name:\n";
        $package_name = (string) trim(readline(">"));

        if(empty($package_name) || !preg_match("/^[a-zA-Z0-9:._]+$/", $package_name)){

            echo "Invalid package name\n";
            exit(1);

        }

        passthru($this->adb_commands[4] . " " . escapeshellarg($package_name));

    }

    private function save_history(string $package_name){

        file_put_contents(
            "removed_apps.txt",
            ">> " . $package_name . PHP_EOL,
            FILE_APPEND
        );

    }


}

$device = new Android_remove();
$device->run($argc, $argv);
