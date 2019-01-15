<!--
    Project 4
    Authors:    Trent Woods & Jared Rigdon
    Date:       11/19/2018
    Purpose:    Read a given JSON file and produce 2 dropdown tables, a text input, and submit button
                Once chosen params are met and submit button is clicked, find requested JSON file and search for any matching terms and input
                and display below the submit button the parent objects that contain a matching sub-object
    
    http://cs.uky.edu/~tmwo226/CS316/proj4/WoodsRigdon_p4.php 
-->

<!DOCTYPE html>
<html>
<head>
    <title>Make your selection</title>
</head>
    <!-- Have the form echo to itself -->
    <form action="<?php echo $_SERVER["PHP_SELF"];?>" method="get">
    <h1>Make your Selection</h1>

    <body>
        <?php
        //global vars that are reset each time the script is run
        //vars to keep track of the number of items in the drop down
        $catCnt = 0;
        $termCnt = 0;

        if(file_exists("DataSources.json")){
            //DataSources.json is the only hardcoded const as this is given as per the requirement 
            $source = file_get_contents("DataSources.json");

            //construct the dropdown
            makeDropdown($source);
        }
        else{
            //provide error message
            echo "Required File for the dropdown menus not found!";
        }

        //------------------------------------------------------------------------------
        //provides the dropdown list and checks for passed in params
        function makeDropdown($source){
            $endSelect = 0;
            $count = 0;
            $step = 0;
            //use RecursiveIteratorIterator to recusively go through the array 
            $jsonIterator = new RecursiveIteratorIterator(
                new RecursiveArrayIterator(json_decode($source, TRUE)),
                RecursiveIteratorIterator::SELF_FIRST);

            foreach ($jsonIterator as $key => $val){
                if(is_array($val)){
                    //this indicates the name of the key (i.e. category or searchterms)
                    if($endSelect==1){
                        echo '</select></br></br>';

                        //reset the counters
                        $endSelect = 0;     //might be redundant
                        $count++;
                        $step = 0;
                    }
                    //Display the Dropdown name value
                    echo "$key:";

                    //create a new dropdown for the curr array using $key as the name
                    if($count == 0){
                        echo '<select name="category" required><option value=""></option>';
                        
                    }else {
                        echo '<select name="whichfield" required><option value=""></option>';
                    }

                    $endSelect = 1;
                }
                else{
                    //$key will represen the key in the curr array and $step will rep the associated value
                    if($count == 0){
                        echo '<option value="'.$step.'">'.$key.'</option>';
                        $catCnt++;
                    }
                    else{
                        echo '<option value="'.$step.'">'.$val.'</option>';
                        $termCnt++;
                    }
                    //using an incremental counter to as per the requirement (and possibly a bit more secure(even more if POST was used))
                    $step++;
                }
            }
            echo '</select></br>';
?>
        </br></br>
        Search For: <input type="text" placeholder="Search Term" name="findme" required></br></br>
        </br></br>
        <input type="submit" value="Search"></br></br>
<?php
            //------------------------------------------------------------------------------
            //checks if the category and searchterms value has been set (and the text input) and if they are passed in
            //params are both ints except for findme
            if(isset($_GET['category']) && isset($_GET['whichfield']) && isset($_GET['findme'])){
                
                //assign index values
                $json_Index = $_GET["category"];
                $term_Index = $_GET["whichfield"];
                $search_Text = $_GET['findme'];

                //check if the values are numerical and within the valid range(known due to given file as per the requirement)
                //note: can either set a global or use a separate function(better) to store numeric range for both dropdowns
                if((ctype_digit($json_Index) && ctype_digit($term_Index)) && ($json_Index >= 0 || $json_Index <= $catCnt) && ($term_Index >= 0 || $term_Index <= $termCnt)){
                    //get desired values
                    $Datasource = getParam($json_Index, 0);

                    $Term = getParam($term_Index, 1);
                    /*
                    for testing

                    echo "$Datasource";
                    echo'<br>';

                    echo "$Term";
                    echo '<br>';

                    echo "$search_Text";
                    echo '<br>';
                    */

                    //call the function to search the desired json
                    searchFile($Datasource, $Term, $search_Text);
                }
            
            }
            else{
                /*
                Display no errors for cases of initial connection
                */
            }
        }

        //------------------------------------------------------------------------------
        //used to find the file, search term, and the input text
        function searchFile($Datasource, $Term, $search_Text){
            //bool to check if any results are found            
            $isResult = false;

            //make sure to check if the file exists first
            if(file_exists($Datasource)){
                //get the content
                $fileSearch = file_get_contents($Datasource);

                $jsonIterator_Search = new RecursiveIteratorIterator(
                    new RecursiveArrayIterator(json_decode($fileSearch, TRUE)),
                    RecursiveIteratorIterator::SELF_FIRST);

                //goes through the multiple arrays(1st level) then 2nd level of each array and so on
                foreach($jsonIterator_Search as $key => $val){
                    //check if the current $val is an array
                    if(is_array($val)){
                        //gets an array of the keys with matching terms
                        //note very similar to array_search but finds all occurences instead of one
                        $keys = array_keys(array_column($val, $Term), $search_Text);

                        //we could break from the current depth to increase search speed but
                        //kept in for cases of matches found as lower levels
                        if(!empty($keys)){
                            //loop through keys array
                            for ($x = 0; $x < sizeof($keys); $x++){

                                //use <pre></pre> to make the resulting array better for viewing
                                echo '<pre>'.print_r($val[$keys[$x]], true).'</pre>';
                            }
                            $isResult = true;
                        }
                    }
                    else{
                        //just for the case that some json file obj in first level is not an array
                        //skips if the term and search_Text matches and no results have been found yet
                        if($key == $Term && $val == $search_Text && !$isResult){
                            echo '<p>'.$key.' ==>'.$val.'</p>';
                        }
                    }
                }
                //if nothing was found, then print out error
                if(!$isResult){
                    echo "No Matches!";
                }
            }
            else{
                //print out error
                echo "Error: Couldn't Locate Desired file.";
            }
        }

        //------------------------------------------------------------------------------
        //func to look through the DataSources.json file and get the associated json and search term for the search 
        function getParam($index, $arrayNum){
            //base file(we assume its there since we could find and open this at the begining of the file)
            $base = file_get_contents("DataSources.json");

            $array = json_decode($base, true);          //decodes it
            $array = array_values($array)[$arrayNum];   //looks at the array section
            $array = array_values($array)[$index];      //gets the value of the index when $cat is

            return $array;  //return the string
        }

        ?>
    </form>

</body>
</html>