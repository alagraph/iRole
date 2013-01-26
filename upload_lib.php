<?php
/**
 * This class allows a user to upload and 
 * validate their files.
 *
 * @author John Ciacia <Sidewinder@extreme-hq.com>
 * @version 1.0
 * @copyright Copyright (c) 2007, John Ciacia
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class Upload {
    
    /**
     *@var string contains the name of the file to be uploaded.
     */
    private $FileName;
    /**
     *@var string contains the temporary name of the file to be uploaded.
     */
    private $TempFileName;
    /**
     *@var string contains directory where the files should be uploaded.
     */
    private $UploadDirectory;
    /**
     *@var string contains an array of valid extensions which are allowed to be uploaded.
     */
    private $ValidExtensions;
    /**
     *@var string contains a message which can be used for debugging.
     */
    private $Message;
    /**
     *@var integer contains maximum size of fiels to be uploaded in bytes.
     */
    private $MaximumFileSize;
    /**
     *@var bool contains whether or not the files being uploaded are images.
     */
    private $IsImage;
    /**
     *@var integer contains maximum width of images to be uploaded.
     */
    private $MaximumWidth;
    /**
     *@var integer contains maximum height of images to be uploaded.
     */
    private $MaximumHeight;
    
    private $FitSize;

    public function __construct($file,
                                $directory,
                                $filename,
                                $validExt=null,
                                $maxSize=null,
                                $maxW=null,
                                $maxH=null,
                                $fit=null){
        
      
      $this->FileName=$filename;        
      $this->TempFileName=$file;
      $this->UploadDirectory=$directory;
      
      $this->MaximumWidth=0;
      $this->MaximumHeight=0;
      
      if(isset($validExt)) $this->ValidExtensions=$validExt;
      if(isset($maxSize)) $this->MaximumFileSize=$maxSize;
      if(isset($maxW)) $this->MaximumWidth=$maxW;
      if(isset($maxH)) $this->MaximumHeight=$maxH;
      
      $this->FitSize=false;
      if(isset($fit)) $this->FitSize=true;


    }

    /**
     *@method bool ValidateExtension() returns whether the extension of file to be uploaded
     *    is allowable or not.
     *@return true the extension is valid.
     *@return false the extension is invalid.
     */
    private function ValidateExtension(){

        $FileName = trim($this->FileName);
        $FileParts = pathinfo($FileName);
        $Extension = strtolower($FileParts['extension']);
        $ValidExtensions = $this->ValidExtensions;

        if (!$FileName) {
            $this->SetMessage("ERROR: File name is empty.");
            return false;
        }

        if (!$ValidExtensions) {
            $this->SetMessage("WARNING: All extensions are valid.");
            return true;
        }

        if (in_array($Extension, $ValidExtensions)) {
            $this->SetMessage("MESSAGE: The extension '$Extension' appears to be valid.");
            return true;
        } else {
            $this->SetMessage("Error: The extension '$Extension' is invalid.");
            return false;  
        }

    }

    /**
     *@method bool ValidateSize() returns whether the file size is acceptable.
     *@return true the size is smaller than the alloted value.
     *@return false the size is larger than the alloted value.
     */
    private function ValidateSize(){
        $MaximumFileSize = $this->MaximumFileSize;
        $TempFileName = $this->GetTempName();
        $TempFileSize = filesize($TempFileName);

        if($MaximumFileSize == "") {
            $this->SetMessage("WARNING: There is no size restriction.");
            return true;
        }

        if ($MaximumFileSize <= $TempFileSize) {
            $this->SetMessage("ERROR: The file is too big. It must be less than $MaximumFileSize and it is $TempFileSize.");
            return false;
        }

        $this->SetMessage("Message: The file size is less than the MaximumFileSize.");
        return true;
    }

    /**
     *@method bool ValidateExistance() determins whether the file already exists. If so, rename $FileName.
     *@return true can never be returned as all file names must be unique.
     *@return false the file name does not exist.
     */
    private function ValidateExistance(){
        $FileName = $this->FileName;
        $UploadDirectory = $this->UploadDirectory;
        $File = $UploadDirectory . $FileName;

        if (file_exists($File)) {
            $this->SetMessage("Message: The file '$FileName' already exist.");
            $UniqueName = rand() . $FileName;
            $this->SetFileName($UniqueName);
            $this->ValidateExistance();
        } else {
            $this->SetMessage("Message: The file name '$FileName' does not exist.");
            return false;
        }
    }

    /**
     *@method bool ValidateDirectory()
     *@return true the UploadDirectory exists, writable, and has a traling slash.
     *@return false the directory was never set, does not exist, or is not writable.
     */
    private function ValidateDirectory(){
        $UploadDirectory = $this->UploadDirectory;

        if (!$UploadDirectory) {
            $this->SetMessage("ERROR: The directory variable is empty.");
            return false;
        }

        if (!is_dir($UploadDirectory)) {
            $this->SetMessage("ERROR: The directory '$UploadDirectory' does not exist.");
            return false;
        }

        if (!is_writable($UploadDirectory)) {
            $this->SetMessage("ERROR: The directory '$UploadDirectory' does not writable.");
            return false;
        }

        if (substr($UploadDirectory, -1) != "/") {
            $this->SetMessage("ERROR: The traling slash does not exist.");
            $NewDirectory = $UploadDirectory . "/";
            $this->SetUploadDirectory($NewDirectory);
            $this->ValidateDirectory();
        } else {
            $this->SetMessage("MESSAGE: The traling slash exist.");
            return true;
        }
    }

    /**
     *@method bool ValidateImage()
     *@return true the image is smaller than the alloted dimensions.
     *@return false the width and/or height is larger then the alloted dimensions.
     */
    private function ValidateImage(){
        $MaximumWidth = $this->MaximumWidth;
        $MaximumHeight = $this->MaximumHeight;
        $TempFileName = $this->TempFileName;

      if($Size = @getimagesize($TempFileName)) {
        $Width = $Size[0];   //$Width is the width in pixels of the image uploaded to the server.
        $Height = $Size[1];  //$Height is the height in pixels of the image uploaded to the server.
        
        if ($this->FitSize){
          $xscale=$Width/$this->MaximumWidth;
          $yscale=$Height/$this->MaximumHeight;
          
          if ($yscale>$xscale){
            $new_width = round($width * (1/$yscale));
            $new_height = round($height * (1/$yscale));
          }
          else {
            $new_width = round($width * (1/$xscale));
            $new_height = round($height * (1/$xscale));
          }
          
          $imageResized = imagecreatetruecolor($new_width, $new_height);
          $imageTmp     = imagecreatefromjpeg ($TempFileName);
          imagecopyresampled($imageResized, $imageTmp, 0, 0, 0, 0, $new_width, $new_height, $Width, $Height);
          $this->TempFileName=$imageResized;
          
          $this->SetMessage("Image Resized.");
          return false;
        }
        
        
        if ($Width > $MaximumWidth && $MaximumWidth>0 ) {
            $this->SetMessage("The width of the image [$Width] exceeds the maximum amount [$MaximumWidth].");
            return false;
        }

        if ($Height > $MaximumHeight && $MaximumHeight>0) {
            $this->SetMessage("The height of the image [$Height] exceeds the maximum amount [$MaximumHeight].");
            return false;
        }

        $this->SetMessage("The image height [$Height] and width [$Width] are within their limitations.");     
        return true;
      }

      $this->SetMessage("Unable to get file Width and Height");
      return true;

    }

    /**
     *@method bool UploadFile() uploads the file to the server after passing all the validations.
     *@return true the file was uploaded.
     *@return false the upload failed.
     */
    public function UploadFile(){

        if (!$this->ValidateExtension()) {
            die($this->GetMessage());
        } 

        else if (!$this->ValidateSize()) {
            die($this->GetMessage());
        }

        else if ($this->ValidateExistance()) {
            die($this->GetMessage());
        }

        else if (!$this->ValidateDirectory()) {
            die($this->GetMessage());
        }

        else if (!$this->ValidateImage()) {
            die($this->GetMessage());
        }

        else {

            $FileName = $this->FileName;
            $TempFileName = $this->TempFileName;
            $UploadDirectory = $this->UploadDirectory;

            if (is_uploaded_file($TempFileName)) { 
                move_uploaded_file($TempFileName, $UploadDirectory . $FileName);
                return true;
            } else {
                return false;
            }

        }

    }

    #Accessors and Mutators beyond this point.
    #Siginificant documentation is not needed.
    
    private function SetMessage($argv){
      $this->Message=$argv;
    }
    
    private function SetFileName($argv){
      $this->FileName=$argv;
    }
  
    function GetFileName()
    {
        return $this->FileName;
    }

    function GetUploadDirectory()
    {
        return $this->UploadDirectory;
    }

    function GetTempName()
    {
        return $this->TempFileName;
    }

    function GetValidExtensions()
    {
        return $this->ValidExtensions;
    }

    function GetMessage()
    {
        if (!isset($this->Message)) {
            $this->SetMessage("No Message");
        }

        return $this->Message;
    }

    function GetMaximumFileSize()
    {
        return $this->MaximumFileSize;
    }

    function GetMaximumWidth()
    {
        return $this->MaximumWidth;
    }

    function GetMaximumHeight()
    {
        return $this->MaximumHeight;
    }
}


?> 