<?php
// Use like so: https://docraptor-php-streaming.herokuapp.com/?url=https%3A%2F%2Fdocraptor.com%2Fsamples%2Finvoice.html&test=1&15743683584

//If no URL in query string, notify
if (!$_GET['url']) {
	echo 'Please include the URL you wish to convert';
	return;
}

//get URL to convert to PDF
$to_convert = urldecode($_GET['url']);

//is Test?
$test = ($_GET['test'])?true:false;
//$test = true;

//if a filename is included, sanitize it, otherwise, create one from $URL
if($_GET['filename']){
	$special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
	$filename = str_replace($special_chars, '', $_GET['filename']);
	$filename = preg_replace('/[\s-]+/', '-', $filename);
	$filename = trim($filename, '.-_');
} else {

	$filename = date('Y-m').'-';

	if($_GET['instance']){
		$filename .= $_GET['instance'].'-';
	}

	$filename .= 'invoice.pdf';
}


require_once('../vendor/docraptor/docraptor/autoload.php');

$configuration = DocRaptor\Configuration::getDefaultConfiguration();
$configuration->setUsername("YOUR_API_KEY_HERE");
//$configuration->setDebug(true);
$docraptor = new DocRaptor\DocApi();

try {
	$doc = new DocRaptor\Doc();
	$doc->setTest($test);
	$doc->setDocumentUrl($to_convert);
	$doc->setName($filename); # help you find a document later
	$doc->setDocumentType("pdf");
	$doc->setPipeline(7);
	$create_response = $docraptor->createAsyncDoc($doc);

	$done = false;
  while (!$done) {
    $status_response = $docraptor->getAsyncDocStatus($create_response->getStatusId());
    switch ($status_response->getStatus()) {
      case "completed":
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename='.$filename);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        ob_clean();
        flush();
        $doc_handle = fopen($status_response->getDownloadUrl(), "r");
        $output_handle = fopen("php://output", "w");

        stream_copy_to_stream($doc_handle, $output_handle);

        fclose($doc_handle);
        fclose($output_handle);

        $done = true;
        break;
      case "failed":
        echo "FALIED\n";
        echo $status_response;
        $done = true;
        break;
      default:
        sleep(1);
    }
  }
} catch (DocRaptor\ApiException $exception) {
	echo $exception . "\n";
	echo $exception->getMessage() . "\n";
	echo $exception->getCode() . "\n";
	echo $exception->getResponseBody() . "\n";
}
