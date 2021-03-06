<?php
/*
 * In employees.php, we display the existing employee table
 * and then provide a form for adding a new employee.
 *
 * prepared statements are used!
 * filtering:
 *	htmlspecialchars() for html filtering
 */

include 'lib353pdo.php';

// main program here
// decide which button was clicked: 'submit' or 'update'

include 'password.php';
$dbname = 'forms';

function submit_form($db, $formid) {
    /* NOT NEEDED - textareas get sent with POST
        $textvals = [];
	foreach ($textareas as $node) {
        $textval =  $node->firstChild->nodeValue;
        //print($textval);
        array_push($textvals, $textval);
    }*/
    // insert text vals into text table

	print ("starting submit_form()<br>\n");
    $name= $_POST['name'];
	$address =  $_POST['address'];
	$position = $_POST['position'];
	$previous = $_POST['previous'];
    $references = $_POST['references'];
    $oldjob = $_POST['oldjob'];
    $interest = $_POST['interest'];
    
    /*
    print(sprintf("Updated textvals:<br> %s<br>\n",
          join("<br>",$textvals)));
    print(sprintf("Updated fields:<br> %s %s %s %s<br>\n",
                  $address, $name, $position, $previous));
     */
    $insertion="update valuetable set value = ? where fieldname = ? and
                html_id = ?";
	$stmt = $db->prepare($insertion);

	if ($stmt == FALSE) {
		print("failed to prepare statement: \"$insertion\"<p>\n");
		$errarray = $db->errorInfo();
		$errmsg = $errarray[2];
		print("<b>Prepare error: $errmsg</b><p>\n");
		die();
	}
    $query_namedArgs = array(['name', $name], ['address', $address],
        ['position', $position], ['previous', $previous],
        ['interest', $interest], ['references', $references],
        ['oldjob', $oldjob]);

    foreach($query_namedArgs as $arg){
        $queryargs = array($arg[1], $arg[0], (int)$formid);
        debug_to_console($queryargs);
    	$ret = $stmt->execute($queryargs);
    	if ($ret == FALSE) {
    		print("execution of query not successful: \"$insertion\"<p>\n");
    		$errarray = $stmt->errorInfo();
    		$errmsg = $errarray[2];
    		print("<b>Execute error: $errmsg</b><p>\n");
    		$fail=1;
    	} else {
    		print sprintf("%s was updated<p>", $arg[0]);
    		$stmt->closeCursor();
    	}
    }


}
main($hostname, $username, $dbname, $password);

function main($hostname, $username, $dbname, $password) {
    // TODO read from db instead
	$form_id = 1;

	// retrieve htmlstr
	// get <form>
	// get list of <input> items
	// for each input item, get its name, retrieve the value from the db, and set it in the DOM
	// for each textarea, do the same
	// print the file
    print "<html><body><h1>Hello here is some stuff</h1>\n";
	$db = connect_pdo($hostname, $username, $password, $dbname);
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {   // submit button pressed
		debug_to_console('got POST'); // why is this going off on page load?
		submit_form($db, $form_id);
	}
	$html = get_html($db, $form_id);
	$dom = new DOMDocument();
	$dom -> loadHTML($html);
	/* */
	$forms     = $dom->getElementsByTagName('form');
	$f0        = $forms[0];		// get first <form> object
	$inputs    = $f0->getElementsByTagName('input');	// get all <input> objects in $f0
	$textareas = $f0->getElementsByTagName('textarea');
	/* */
	foreach ($inputs as $node) {
		$name = getnameattr($node);
		$val  = get_value($db, $form_id, $name);
    		if ($name != "submit"){ //Dont overwrite submit button...
			$node->setAttribute("value", $val);	// use getAttribute() to retrieve string
    		}
        }
	foreach ($textareas as $node) {
		$name = getnameattr($node);
		$val  = get_value($db, $form_id, $name);
                print "updating textarea $name\n";
		//$node->setAttribute("value", $val);		// pld: this does NOT work
		// before calling appendChild(), it *might* be worth seeing if there's an existing child we can replace.
		$node->appendChild( $dom->createTextNode($val));
		// pld: use $node->childNodes[0]->textContent to retrieve [??]
		// pld: maybe $node->firstChild->textContent is better (this is not a function!)
    }
	/* */
	$htmlout =  $dom->saveHTML() ;			// generates html of entire document
        print "$htmlout";
	//debug_to_console($_POST);
}
function debug_to_console( $data ) {
    $output = $data;
    if ( is_array( $output ) )
        $output = implode( ',', $output);

    echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
}

function get_value($db, $form_id, $name) {
	// retrieve the appropriate value

	$query="select v.value from valuetable v where html_id = ? and fieldname = ?";

	$stmt = $db->prepare($query);

	if ($stmt == FALSE) {
		print("failed to prepare statement: \"$query\"<p>\n");
		$errarray = $db->errorInfo();
		$errmsg = $errarray[2];
		print("<b>Prepare error: $errmsg</b><p>\n");	// error would live in $db
		die();
	}

	$queryargs = array($form_id, $name);

	$ret = $stmt->execute($queryargs);

	if ($ret == FALSE) {
		print("execution of query not successful: \"$insertion\"<p>\n");
		$errarray = $stmt->errorInfo();
		$errmsg = $errarray[2];
		print("<b>Execute error: $errmsg</b><p>\n");
		$fail=1;
	}

	$col = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
	if (count($col) == 0) {
	    return "";
	}
	$item = $col[0];
	$csize = count($col);
	return $item;
}

function get_html($db, $form_id) {
	// retrieve the appropriate value

	$query="select h.html_str from htmltable h where html_id = ?";

	$stmt = $db->prepare($query);

	if ($stmt == FALSE) {
		print("failed to prepare statement: \"$query\"<p>\n");
		$errarray = $db->errorInfo();
		$errmsg = $errarray[2];
		print("<b>Prepare error: $errmsg</b><p>\n");	// error would live in $db
		die();
	}

	$queryargs = array($form_id);

	$ret = $stmt->execute($queryargs);

	if ($ret == FALSE) {
		print("execution of query not successful: \"$insertion\"<p>\n");
		$errarray = $stmt->errorInfo();
		$errmsg = $errarray[2];
		print("<b>Execute error: $errmsg</b><p>\n");
		$fail=1;
	}

	$class = get_class($stmt);

	$row = $stmt->fetchAll(PDO::FETCH_NUM);
	$item = $row[0][0];
	return $item;
}

// in the following, $node can be an <input> or any other node with a name.
function getnameattr($node) {
    $name = $node->getAttribute('name');
    if ($name == '') {
        print ("warning: node $node has no attribute 'name'\n");
    }
    return $name;
}


?>
