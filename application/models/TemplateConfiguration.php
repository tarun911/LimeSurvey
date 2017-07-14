<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
* LimeSurvey
* Copyright (C) 2007-2015 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

/*
 * This is the model class for table "{{template_configuration}}".
 *
 * The followings are the available columns in table '{{template_configuration}}':
 * @property string $id
 * @property string $templates_name
 * @property string $gsid
 * @property string $sid
 * @property string $files_css
 * @property string $files_js
 * @property string $files_print_css
 * @property string $options
 * @property string $cssframework_name
 * @property string $cssframework_css
 * @property string $cssframework_js
 * @property string $viewdirectory
 * @property string $filesdirectory
 * @property string $packages_to_load
 * @property string $packages_rtl
 *
 *
 * @package       LimeSurvey
 * @subpackage    Backend
 */
class TemplateConfiguration extends CActiveRecord
{
    /** @var string $sTemplateName The template name */
    public $sTemplateName='';

    /** @var string $sPackageName Name of the asset package of this template*/
    public $sPackageName;

    /** @var  string $path Path of this template */
    public $path;

    /** @var string[] $sTemplateurl Url to reach the framework */
    public $sTemplateurl;

    /** @var  string $viewPath Path of the views files (twig template) */
    public $viewPath;

    /** @var  string $filesPath Path of the tmeplate's files */
    public $filesPath;

    /** @var string[] $cssFramework What framework css is used */
    public $cssFramework;

    /** @var boolean $isStandard Is this template a core one? */
    public $isStandard;

    /** @var SimpleXMLElement $config Will contain the config.xml */
    public $config;

    /** @var TemplateConfiguration $oMotherTemplate The template name */
    public $oMotherTemplate;

    /** @var SimpleXMLElement $oOptions The template options */
    public $oOptions;


    /** @var string $iSurveyId The current Survey Id. It can be void. It's use only to retreive the current template of a given survey */
    private $iSurveyId='';

    /** @var string $hasConfigFile Does it has a config.xml file? */
    private $hasConfigFile='';//

    /** @var stdClass[] $packages Array of package dependencies defined in config.xml*/
    private $packages;

    /** @var string[] $depends List of all dependencies (could be more that just the config.xml packages) */
    private $depends = array();

    /** @var string $xmlFile What xml config file does it use? (config/minimal) */
    private $xmlFile;

    /**  @var integer $apiVersion: Version of the LS API when created. Must be private : disallow update */
    private $apiVersion;
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{template_configuration}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('templates_name', 'required'),
            array('id, sid, gsid', 'numerical', 'integerOnly'=>true),
            array('templates_name', 'length', 'max'=>150),
            array('cssframework_name', 'length', 'max'=>45),
            array('files_css, files_js, files_print_css, options, cssframework_css, cssframework_js, packages_to_load', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, templates_name, sid, gsid, files_css, files_js, files_print_css, options, cssframework_name, cssframework_css, cssframework_js, packages_to_load', 'safe', 'on'=>'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array(
            'template' => array(self::HAS_ONE, 'Template', array('name' => 'templates_name')),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'templates_name' => 'Templates Name',
            'sid' => 'Sid',
            'gsid' => 'Gsid',
            'files_css' => 'Files Css',
            'files_js' => 'Files Js',
            'files_print_css' => 'Files Print Css',
            'options' => 'Options',
            'cssframework_name' => 'Cssframework Name',
            'cssframework_css' => 'Cssframework Css',
            'cssframework_js' => 'Cssframework Js',
            'packages_to_load' => 'Packages To Load',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria=new CDbCriteria;

        $criteria->compare('id',$this->id);
        $criteria->compare('templates_name',$this->templates_name,true);
        $criteria->compare('sid',$this->sid);
        $criteria->compare('gsid',$this->gsid);
        $criteria->compare('files_css',$this->files_css,true);
        $criteria->compare('files_js',$this->files_js,true);
        $criteria->compare('files_print_css',$this->files_print_css,true);
        $criteria->compare('options',$this->options,true);
        $criteria->compare('cssframework_name',$this->cssframework_name,true);
        $criteria->compare('cssframework_css',$this->cssframework_css,true);
        $criteria->compare('cssframework_js',$this->cssframework_js,true);
        $criteria->compare('packages_to_load',$this->packages_to_load,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }



    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return TemplateConfigurationDB the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Create a new entry in {{templates}} table using the template manifest
     * @param string $sTemplateName the name of the template to import
     * @return mixed true on success | exception
     */
    public static function importManifest($sTemplateName)
    {
        $oEditedTemplate                      = Template::model()->getTemplateConfiguration($sTemplateName, '', false);
        $oEditTemplateDb                      = Template::model()->findByPk($oEditedTemplate->oMotherTemplate->sTemplateName);
        $oNewTemplate                         = new Template;
        $oNewTemplate->name                   = $oEditedTemplate->sTemplateName;
        $oNewTemplate->folder                 = $oEditedTemplate->sTemplateName;
        $oNewTemplate->title                  = $oEditedTemplate->sTemplateName;  // For now, when created via template editor => name == folder == title
        $oNewTemplate->creation_date          = date("Y-m-d H:i:s");
        $oNewTemplate->author                 = Yii::app()->user->name;
        $oNewTemplate->author_email           = ''; // privacy
        $oNewTemplate->author_url             = ''; // privacy
        $oNewTemplate->api_version            = $oEditTemplateDb->api_version;
        $oNewTemplate->view_folder            = $oEditTemplateDb->view_folder;
        $oNewTemplate->files_folder           = $oEditTemplateDb->files_folder;
        //$oNewTemplate->description           TODO: a more complex modal whith email, author, url, licence, desc, etc
        $oNewTemplate->owner_id               = Yii::app()->user->id;
        $oNewTemplate->extends_templates_name = $oEditedTemplate->oMotherTemplate->sTemplateName;

        if ($oNewTemplate->save()){
            $oNewTemplateConfiguration                    = new TemplateConfiguration;
            $oNewTemplateConfiguration->templates_name    = $oEditedTemplate->sTemplateName;
            if ($oNewTemplateConfiguration->save()){
                return true;
            }else{
                throw new Exception($oNewTemplateConfiguration->getErrors());
            }
        }else{
            throw new Exception($oNewTemplate->getErrors());
        }
    }

    /**
     * Constructs a template configuration object
     * If any problem (like template doesn't exist), it will load the default template configuration
     *
     * @param  string $sTemplateName the name of the template to load. The string comes from the template selector in survey settings
     * @param  string $iSurveyId the id of the survey. If
     * @return $this
     */
    public function setTemplateConfiguration($sTemplateName='', $iSurveyId='')
    {
        $this->sTemplateName = $this->template->name;
        $this->setIsStandard();                                                 // Check if  it is a CORE template
        $this->path = ($this->isStandard)?Yii::app()->getConfig("standardtemplaterootdir").DIRECTORY_SEPARATOR.$this->template->folder:Yii::app()->getConfig("usertemplaterootdir").DIRECTORY_SEPARATOR.$this->template->folder;
        $this->setMotherTemplates();                                            // Recursive mother templates configuration
        @$this->setThisTemplate();                                               // Set the main config values of this template
        $this->createTemplatePackage($this);                                    // Create an asset package ready to be loaded
        return $this;
    }

    /**
     * get the template API version
     * @return integer
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
    * Returns the complete URL path to a given template name
    *
    * @param string $sTemplateName
    * @return string template url
    */
    public function getTemplateURL()
    {
        if(!isset($this->sTemplateurl)){
            $this->sTemplateurl = Template::getTemplateURL($this->sTemplateName);
        }
        return $this->sTemplateurl;
    }

    /**
     * Add a file replacement in the field `file_{css|js|print_css}` in table {{template_configuration}},
     * eg: {"replace": [ {original files to replace here...}, "css/template.css",]}
     *
     * @param string $sFile the file to replace
     * @param string $sType css|js
     */
    public function addFileReplacement($sFile, $sType)
    {
        $sField = 'files_'.$sType;
        $oFiles = (array) json_decode($this->$sField);

        $oFiles['replace'][] = $sFile;

        $this->$sField = json_encode($oFiles);

        if ($this->save()){
            return true;
        }else{
            throw new Exception("could not add $sFile to  $sField replacements! ".$this->getErrors());
        }
    }

    /**
    * Get the template for a given file. It checks if a file exist in the current template or in one of its mother templates
    *
    * @param  string $sFile      the  file to look for (must contain relative path, unless it's a view file)
    * @param string $oRTemplate template from which the recurrence should start
    * @return TemplateManifest
    */
    public function getTemplateForFile($sFile, $oRTemplate)
    {
        while (!file_exists($oRTemplate->path.'/'.$sFile) && !file_exists($oRTemplate->viewPath.$sFile)){
            $oMotherTemplate = $oRTemplate->oMotherTemplate;
            if(!($oMotherTemplate instanceof TemplateConfiguration)){
                return false;
                break;
            }
            $oRTemplate = $oMotherTemplate;
        }

        return $oRTemplate;
    }


    /**
     * Create a package for the asset manager.
     * The asset manager will push to tmp/assets/xyxyxy/ the whole template directory (with css, js, files, etc.)
     * And it will publish the CSS and the JS defined in config.xml. So CSS can use relative path for pictures.
     * The publication of the package itself is in LSETwigViewRenderer::renderTemplateFromString()
     *
     */
    private function createTemplatePackage($oTemplate)
    {
        // Each template in the inheritance tree needs a specific alias
        $sPathName  = 'survey.template-'.$oTemplate->sTemplateName.'.path';
        $sViewName  = 'survey.template-'.$oTemplate->sTemplateName.'.viewpath';

        Yii::setPathOfAlias($sPathName, $oTemplate->path);
        Yii::setPathOfAlias($sViewName, $oTemplate->viewPath);

        $aCssFiles = $aJsFiles = array();

        // First we add the framework replacement (bootstrap.css must be loaded before template.css)
        $aCssFiles = $this->getFrameworkAssetsToReplace('css');
        $aJsFiles  = $this->getFrameworkAssetsToReplace('js');

        // Then we add the template config files
        $aTCssFiles = $this->getFilesToLoad($this->files_css);
        $aTJsFiles  = $this->getFilesToLoad($this->files_js);

        $aCssFiles    = array_merge($aCssFiles, $aTCssFiles);
        $aTJsFiles    = array_merge($aCssFiles, $aTJsFiles);

        $dir       = getLanguageRTL(App()->language) ? 'rtl' : 'ltr';

        // Remove/Replace mother files
        $this->changeMotherConfiguration('css', $aCssFiles);
        $this->changeMotherConfiguration('js',  $aJsFiles);

        // Then we add the direction files if they exist
        // TODO: add rtl fields in db, or an attribute system (dir="rtl", etc)

        $this->sPackageName = 'survey-template-'.$this->sTemplateName;
        $sTemplateurl       = $oTemplate->getTemplateURL();

        // The package "survey-template-{sTemplateName}" will be available from anywhere in the app now.
        // To publish it : Yii::app()->clientScript->registerPackage( 'survey-template-{sTemplateName}' );
        // Depending on settings, it will create the asset directory, and publish the css and js files
        Yii::app()->clientScript->addPackage( $this->sPackageName, array(
            'devBaseUrl'  => $sTemplateurl,                                     // Used when asset manager is off
            'basePath'    => $sPathName,                                        // Used when asset manager is on
            'css'         => $aCssFiles,
            'js'          => $aJsFiles,
            'depends'     => $oTemplate->depends,
        ) );
    }

    /**
     * From a list of json files in db it will generate a PHP array ready to use by removeFileFromPackage()
     *
     * @var $jFiles string json
     * @return array
     */
    private function getFilesToLoad($jFiles)
    {
       $aFiles = array();
       if(!empty($jFiles)){
           $oFiles = json_decode($jFiles);
           foreach($oFiles as $action => $aFileList){
               $aFiles = array_merge($aFiles, $aFileList);
           }
       }
       return $aFiles;
    }

    /**
     * Change the mother template configuration depending on template settings
     * @var $sType     string   the type of settings to change (css or js)
     * @var $aSettings array    array of local setting
     * @return array
     */
    private function changeMotherConfiguration( $sType, $aSettings )
    {
        if (is_a($this->oMotherTemplate, 'TemplateConfiguration')){
            $this->removeFileFromPackage($this->oMotherTemplate->sPackageName, $sType, $aSettings);
        }
    }

    /**
     * Proxy for Yii::app()->clientScript->removeFileFromPackage()
     *
     * @param $sPackageName     string   name of the package to edit
     * @param $sType            string   the type of settings to change (css or js)
     * @param $aSettings        array    array of local setting
     * @return array
     */
    private function removeFileFromPackage( $sPackageName, $sType, $aSettings )
    {
        foreach( $aSettings as $sFile){
            Yii::app()->clientScript->removeFileFromPackage($sPackageName, $sType, $sFile );
        }
    }

    /**
     * Configure the mother template (and its mother templates)
     * This is an object recursive call to TemplateConfiguration::setTemplateConfiguration()
     */
    private function setMotherTemplates()
    {
        if(!empty($this->template->extends_templates_name)){
            $sMotherTemplateName   = $this->template->extends_templates_name;
            $this->oMotherTemplate = Template::getTemplateConfiguration($sMotherTemplateName);

            if ($this->oMotherTemplate->checkTemplate()){
                $this->oMotherTemplate->setTemplateConfiguration($sMotherTemplateName); // Object Recursion
            }else{
                // Throw exception? Set to default template?
            }
        }
    }

    public function checkTemplate()
    {
        if (is_object($this->template) && !is_dir(Yii::app()->getConfig("standardtemplaterootdir").DIRECTORY_SEPARATOR.$this->template->folder)&& !is_dir(Yii::app()->getConfig("usertemplaterootdir").DIRECTORY_SEPARATOR.$this->template->folder)){
            return false;
        }
        return true;
    }

    /**
     * Set the default configuration values for the template, and use the motherTemplate value if needed
     */
    private function setThisTemplate()
    {
        // Mandtory setting in config XML (can be not set in inheritance tree, but must be set in mother template (void value is still a setting))
        $this->apiVersion               = (!empty($this->template->api_version))? $this->template->api_version : $this->oMotherTemplate->apiVersion;
        $this->viewPath                 = (!empty($this->template->view_folder))  ? $this->path.DIRECTORY_SEPARATOR.$this->template->view_folder.DIRECTORY_SEPARATOR : $this->path.DIRECTORY_SEPARATOR.$this->oMotherTemplate->view_folder.DIRECTORY_SEPARATOR;
        $this->filesPath                = (!empty($this->template->files_folder))  ? $this->path.DIRECTORY_SEPARATOR.$this->template->files_folder.DIRECTORY_SEPARATOR   :  $this->path.DIRECTORY_SEPARATOR.$this->oMotherTemplate->file_folder.DIRECTORY_SEPARATOR;


        // Options are optional
        // TODO: twig getOption should return mother template option when option = inherit
        $this->oOptions = array();
        if (!empty($this->options)){
            $this->oOptions[] = (array) json_decode($this->options);
        }elseif(!empty($this->oMotherTemplate->oOptions)){
            $this->oOptions[] = $this->oMotherTemplate->oOptions;
        }

        // Not mandatory (use package dependances)
        if (!empty($this->cssframework_name)){
            $this->cssFramework = new \stdClass();
            $this->cssFramework->name = $this->cssframework_name;
            $this->cssFramework->css  = json_decode($this->cssframework_css);
            $this->cssFramework->js   = json_decode($this->cssframework_js);

        }else{
            $this->cssFramework = '';
        }

        if (!empty($this->packages_to_load)){
            $this->packages = json_decode($this->packages_to_load);
        }

        // Add depend package according to packages
        $this->depends                  = array_merge($this->depends, $this->getDependsPackages($this));
    }


    /**
     * @return bool
     */
    private function setIsStandard()
    {
        $this->isStandard = Template::isStandardTemplate($this->sTemplateName);
    }


    /**
     * Get the depends package
     * @uses self::@package
     * @return string[]
     */
    private function getDependsPackages($oTemplate)
    {

        $dir = (getLanguageRTL(App()->getLanguage()))?'rtl':'ltr';

        /* Core package */
        $packages[]='limesurvey-public';
        $packages[] = 'template-core';
        $packages[] = ( $dir == "ltr")? 'template-core-ltr' : 'template-core-rtl'; // Awesome Bootstrap Checkboxes

        /* bootstrap */
        if(!empty($this->cssFramework)){

            // Basic bootstrap package
            if((string)$this->cssFramework->name == "bootstrap"){
                $packages[] = 'bootstrap';
            }

            // Rtl version of bootstrap
            if ($dir == "rtl"){
                $packages[] = 'bootstrap-rtl';
            }

            // Remove unwanted bootstrap stuff
            foreach( $this->getFrameworkAssetsToReplace('css', true) as $toReplace){
                Yii::app()->clientScript->removeFileFromPackage('bootstrap', 'css', $toReplace );
            }

            foreach( $this->getFrameworkAssetsToReplace('js', true) as $toReplace){
                Yii::app()->clientScript->removeFileFromPackage('bootstrap', 'js', $toReplace );
            }
        }

        /* Moter Template */
        if (!empty($this->template->extends_templates_name)){
            $sMotherTemplateName = (string) $this->template->extends_templates_name;
            $packages[]          = 'survey-template-'.$sMotherTemplateName;
        }

        return $packages;
    }

    /**
     * Get the list of file replacement from Engine Framework
     * @param string  $sType            css|js the type of file
     * @param boolean $bInlcudeRemove   also get the files to remove
     * @return array
     */
    private function getFrameworkAssetsToReplace( $sType, $bInlcudeRemove = false)
    {
        //  foreach( $this->getFrameworkAssetsToReplace('css', true) as $toReplace){

        $sFieldName  = 'cssframework_'.$sType;
        $aFieldValue = (array) json_decode($this->$sFieldName);

        $aAssetsToRemove = array();
        if (!empty( $aFieldValue )){
            $aAssetsToRemove = (array) $aFieldValue['replace'] ;
            if($bInlcudeRemove){
                $aAssetsToRemove = array_merge($aAssetsToRemove, (array) $aFieldValue['remove'] );
            }
        }
        return $aAssetsToRemove;
    }

    /**
     * Get the file path for a given template.
     * It will check if css/js (relative to path), or view (view path)
     * It will search for current template and mother templates
     *
     * @param   string  $sFile          relative path to the file
     * @param   string  $oTemplate      the template where to look for (and its mother templates)
     */
    private function getFilePath($sFile, $oTemplate)
    {
        // Remove relative path
        $sFile = trim($sFile, '.');
        $sFile = trim($sFile, '/');

        // Retreive the correct template for this file (can be a mother template)
        $oTemplate = $this->getTemplateForFile($sFile, $oTemplate);

        if($oTemplate instanceof TemplateConfiguration){
            if(file_exists($oTemplate->path.'/'.$sFile)){
                return $oTemplate->path.'/'.$sFile;
            }elseif(file_exists($oTemplate->viewPath.$sFile)){
                return $oTemplate->viewPath.$sFile;
            }
        }
        return false;
    }

}
