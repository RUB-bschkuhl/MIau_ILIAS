<?php
/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

require_once 'Modules/Test/classes/inc.AssessmentConstants.php';

/**
 * Class ilObjTest
 *
 * @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @defgroup ModulesTest Modules/Test
 * @extends ilObject
 */
class ilObjTest extends ilObject implements ilMarkSchemaAware, ilEctsGradesEnabled
{
    public const DEFAULT_PROCESSING_TIME_MINUTES = 90;

    #region Properties

    public const QUESTION_SET_TYPE_FIXED = 'FIXED_QUEST_SET';
    public const QUESTION_SET_TYPE_RANDOM = 'RANDOM_QUEST_SET';
    private const QUESTION_SET_TYPE_DYNAMIC = 'DYNAMIC_QUEST_SET';

    private string $questionSetType = self::QUESTION_SET_TYPE_FIXED;
    private bool $skillServiceEnabled = false;
    private array $resultFilterTaxIds = array();
    private ?int $specific_answer_feedback = null;
    private ?bool $activation_limited = null;
    private array $mob_ids;
    private array $file_ids;
    private bool $online;
    private \ILIAS\Test\InternalRequestService $testrequest;
    protected int $_kiosk;
    public int $test_id;
    public int $invitation = INVITATION_OFF;
    public string $author;

    /**
     * A reference to an IMS compatible matadata set
     */
    public $metadata;
    public array $questions;
    protected bool $introductionEnabled;
    protected string $introduction;

    /**
     * Defines the mark schema
     * ASS_MarkSchema ?
     */
    public $mark_schema;
    public int $sequence_settings;
    public int $score_reporting;
    public int $instant_verification;
    public int $answer_feedback_points;
    public $reporting_date;

    /**
     * Contains the evaluation data settings the tutor defines for the user
     */
    public $evaluation_data;
    public int $nr_of_tries;
    protected bool $blockPassesAfterPassedEnabled = false;
    public int $use_previous_answers;
    public int $title_output;
    public $processing_time; // Initialized as string, but cannot be declared
    public $enable_processing_time; // Initialized as int, but cannot be declared
    public int $reset_processing_time;

    /**
     * bool?
     */
    protected $starting_time_enabled;

    protected ?string $starting_time;

    /**
     * bool?
     */
    protected $ending_time_enabled;

    protected ?string $ending_time;

    /**
     * Indicates if ECTS grades will be used
     * @var int|boolean
     */
    protected $ects_output = false;

    /**
     * Contains the percentage of maximum points a failed user needs to get the FX ECTS grade
     */
    protected ?float $ects_fx = null;
    protected array $ects_grades = array();

    protected $enabled_view_mode;
    public bool $shuffle_questions;

    /**
     * Determines wheather or not a question summary is shown to the users
     *
     * @var bool|int
     */
    public $show_summary;

    /**
     * bool?
     */
    protected $passwordEnabled;
    protected ?string $password;

    /**
     * bool?
     */
    protected $limitUsersEnabled;

    /**
     * number of allowed users for the test
     *
     * @var int|string
     */
    protected $allowedUsers;

    /**
     * inactivity time gap of the allowed users to let new users into the test
     *
     * @var int|string
     */
    protected $allowedUsersTimeGap;

    public int $anonymity;

    public int $show_cancel;

    public int $show_marker;

    public int $fixed_participants;

    public int $answer_feedback;

    /**
     * contains the test session data
     */
    public $testSession;

    /**
     * contains the test sequence data
     */
    public $testSequence;

    /**
    * Determines whether or not a final statement should be shown on test completion
    *
    * @var integer|bool
    */
    private $_showfinalstatement;
    private string $_finalstatement;

    private bool $_showinfo;

    private bool $_forcejs = true;

    /**
    * Name of a custom style sheet for the test
    * string?
    */
    private $_customStyle;

    protected $mailnotification;

    protected int $mailnottype;

    protected int $exportsettings;

    private $template_id;

    protected $oldOnlineStatus = null;

    protected bool $print_best_solution_with_result = true;

    private ?bool $offeringQuestionHintsEnabled = null;

    private ?bool $obligationsEnabled = null;

    protected $activation_visibility;

    protected $activation_starting_time;

    protected $activation_ending_time;

    protected bool $autosave;
    protected int $autosave_ival;

    /**
     * defines wether it is possible for users
     * to delete their own test passes or not
     *
     * @var boolean
     */
    private $passDeletionAllowed = null;

    /**
     * holds the fact wether participant data exists or not
     * DO NOT USE TIS PROPERTY DRIRECTLY
     * ALWAYS USE ilObjTest::paricipantDataExist() since this method initialises this property
     */
    private $participantDataExist = null;

    protected bool $enable_examview;
    protected bool $show_examview_html;
    protected bool $show_examview_pdf;
    protected bool $enable_archiving;

    private int $redirection_mode = 0;
    private ?string $redirection_url = null;

    protected bool $show_exam_id_in_test_pass_enabled;
    protected bool $show_exam_id_in_test_results_enabled;

    protected bool $sign_submission;

    /** @var mixed availability of selector for special characters  */
    protected $char_selector_availability;
    protected ?string $char_selector_definition;

    protected bool $showGradingStatusEnabled;
    protected bool $showGradingMarkEnabled;

    protected bool $followupQuestionAnswerFixationEnabled;
    protected bool $instantFeedbackAnswerFixationEnabled;

    /**
     * @var bool
     */
    protected $forceInstantFeedbackEnabled;

    protected bool $testFinalBroken;

    private ?int $tmpCopyWizardCopyId;

    /**
     * @var string mm:ddd:hh:ii:ss
     */
    protected $pass_waiting = "00:000:00:00:00";
    #endregion

    protected ilDBInterface $db;
    protected ?ilObjTestScoreSettings $score_settings = null;
    protected ?ScoreSettingsRepository $score_settings_repo = null;

    /**
     * Constructor
     *
     * @param	$a_id 					int|string	Reference_id or object_id.
     * @param	$a_call_by_reference	bool		Treat the id as reference_id (true) or object_id (false).
     */
    public function __construct($a_id = 0, bool $a_call_by_reference = true)
    {
        global $DIC;
        $this->db = $DIC['ilDB'];
        $ilUser = $DIC['ilUser'];
        $lng = $DIC['lng'];
        $this->type = "tst";
        $this->testrequest = $DIC->test()->internal()->request();

        $lng->loadLanguageModule("assessment");
        $this->mark_schema = new ASS_MarkSchema();
        $this->mark_schema->createSimpleSchema(
            $lng->txt("failed_short"),
            $lng->txt("failed_official"),
            0,
            0,
            $lng->txt("passed_short"),
            $lng->txt("passed_official"),
            50,
            1
        );

        $this->score_settings = null;
        $this->test_id = -1;
        $this->author = $ilUser->fullname;
        $this->introductionEnabled = false;
        $this->introduction = "";
        $this->questions = array();
        $this->sequence_settings = TEST_FIXED_SEQUENCE;
        $this->score_reporting = self::SCORE_REPORTING_FINISHED;
        $this->instant_verification = 0;
        $this->answer_feedback_points = 0;
        $this->reporting_date = "";
        $this->nr_of_tries = 0;
        $this->_kiosk = 0;
        $this->use_previous_answers = 1;
        $this->title_output = 0;
        $this->starting_time = "";
        $this->ending_time = "";
        $this->processing_time = "";
        $this->enable_processing_time = "0";
        $this->reset_processing_time = 0;
        $this->ects_output = false;
        $this->ects_fx = null;
        $this->shuffle_questions = false;
        $this->mailnottype = 0;
        $this->show_summary = 8;
        $this->answer_feedback = 0;
        $this->password = "";
        $this->allowedUsers = "";
        $this->_showfinalstatement = false;
        $this->_finalstatement = "";
        $this->_showinfo = true;
        $this->_forcejs = true;
        $this->_customStyle = "";
        $this->allowedUsersTimeGap = "";
        $this->anonymity = 0;
        $this->show_cancel = 0;
        $this->show_marker = 0;
        $this->fixed_participants = 0;
        $this->testSession = false;
        $this->testSequence = false;
        $this->mailnotification = 0;

        $this->ects_grades = array(
            'A' => 90,
            'B' => 65,
            'C' => 35,
            'D' => 10,
            'E' => 0
        );

        $this->autosave = false;
        $this->autosave_ival = 30000;

        $this->enable_examview = false;
        $this->show_examview_html = false;
        $this->show_examview_pdf = false;
        $this->enable_archiving = false;

        $this->template_id = '';
        $this->redirection_mode = 0;
        $this->redirection_url = null;
        $this->show_exam_id_in_test_pass_enabled = false;
        $this->sign_submission = false;
        $this->char_selector_availability = 0;
        $this->char_selector_definition = null;

        $this->showGradingStatusEnabled = true;
        $this->showGradingMarkEnabled = true;

        $this->followupQuestionAnswerFixationEnabled = false;
        $this->instantFeedbackAnswerFixationEnabled = false;

        $this->testFinalBroken = false;

        $this->tmpCopyWizardCopyId = null;

        parent::__construct($a_id, $a_call_by_reference);
    }

    /**
     * returns the object title prepared to be used as a filename
     */
    public function getTitleFilenameCompliant(): string
    {
        return ilFileUtils::getASCIIFilename($this->getTitle());
    }

    public function getTmpCopyWizardCopyId(): ?int
    {
        return $this->tmpCopyWizardCopyId;
    }

    public function setTmpCopyWizardCopyId(int $tmpCopyWizardCopyId): void
    {
        $this->tmpCopyWizardCopyId = $tmpCopyWizardCopyId;
    }

    public function create(): int
    {
        $this->setOfflineStatus(true);
        $id = parent::create();
        $this->createMetaData();
        return $id;
    }

    public function update(): bool
    {
        if (!parent::update()) {
            return false;
        }

        // put here object specific stuff
        $this->updateMetaData();
        return true;
    }

    public function read(): void
    {
        parent::read();
        $this->loadFromDb();
    }

    public function delete(): bool
    {
        // always call parent delete function first!!
        if (!parent::delete()) {
            return false;
        }

        // delet meta data
        $this->deleteMetaData();

        //put here your module specific stuff
        $this->deleteTest();

        $qsaImportFails = new ilAssQuestionSkillAssignmentImportFails($this->getId());
        $qsaImportFails->deleteRegisteredImportFails();
        $sltImportFails = new ilTestSkillLevelThresholdImportFails($this->getId());
        $sltImportFails->deleteRegisteredImportFails();

        return true;
    }

    public function deleteTest(): void
    {
        global $DIC;
        $tree = $DIC['tree'];
        $ilDB = $DIC['ilDB'];
        $component_repository = $DIC['component.repository'];
        $lng = $DIC['lng'];

        $participantData = new ilTestParticipantData($ilDB, $lng);
        $participantData->load($this->getTestId());
        $this->removeTestResults($participantData);

        $ilDB->manipulateF(
            "DELETE FROM tst_mark WHERE test_fi = %s",
            array('integer'),
            array($this->getTestId())
        );

        $ilDB->manipulateF(
            "DELETE FROM tst_tests WHERE test_id = %s",
            array('integer'),
            array($this->getTestId())
        );

        $testQuestionSetConfigFactory = new ilTestQuestionSetConfigFactory($tree, $ilDB, $component_repository, $this);
        $testQuestionSetConfigFactory->getQuestionSetConfig()->removeQuestionSetRelatedData();

        $tst_data_dir = ilFileUtils::getDataDir() . "/tst_data";
        $directory = $tst_data_dir . "/tst_" . $this->getId();
        if (is_dir($directory)) {
            ilFileUtils::delDir($directory);
        }
        $mobs = ilObjMediaObject::_getMobsOfObject("tst:html", $this->getId());
        // remaining usages are not in text anymore -> delete them
        // and media objects (note: delete method of ilObjMediaObject
        // checks whether object is used in another context; if yes,
        // the object is not deleted!)
        foreach ($mobs as $mob) {
            ilObjMediaObject::_removeUsage($mob, "tst:html", $this->getId());
            if (ilObjMediaObject::_exists($mob)) {
                $mob_obj = new ilObjMediaObject($mob);
                $mob_obj->delete();
            }
        }
    }

    /**
    * creates data directory for export files
    * (data_dir/tst_data/tst_<id>/export, depending on data
    * directory that is set in ILIAS setup/ini)
    */
    public function createExportDirectory(): void
    {
        $tst_data_dir = ilFileUtils::getDataDir() . "/tst_data";
        ilFileUtils::makeDir($tst_data_dir);
        if (!is_writable($tst_data_dir)) {
            $this->ilias->raiseError("Test Data Directory (" . $tst_data_dir
                . ") not writeable.", $this->ilias->error_obj->MESSAGE);
        }

        // create learning module directory (data_dir/lm_data/lm_<id>)
        $tst_dir = $tst_data_dir . "/tst_" . $this->getId();
        ilFileUtils::makeDir($tst_dir);
        if (!@is_dir($tst_dir)) {
            $this->ilias->raiseError("Creation of Test Directory failed.", $this->ilias->error_obj->MESSAGE);
        }
        // create Export subdirectory (data_dir/lm_data/lm_<id>/Export)
        $export_dir = $tst_dir . "/export";
        ilFileUtils::makeDir($export_dir);
        if (!@is_dir($export_dir)) {
            $this->ilias->raiseError("Creation of Export Directory failed.", $this->ilias->error_obj->MESSAGE);
        }
    }

    public function getExportDirectory(): string
    {
        $export_dir = ilFileUtils::getDataDir() . "/tst_data" . "/tst_" . $this->getId() . "/export";
        return $export_dir;
    }

    /**
     * Get a list of the already exported files in the export directory
     *
     * @return array A list of file names
     */
    public function getExportFiles(string $dir = ''): array
    {
        // quit if import dir not available
        if (!@is_dir($dir) || !is_writable($dir)) {
            return array();
        }

        $files = array();
        foreach (new DirectoryIterator($dir) as $file) {
            /**
             * @var $file SplFileInfo
             */
            if ($file->isDir()) {
                continue;
            }

            $files[] = $file->getBasename();
        }

        sort($files);

        return $files;
    }

    public static function _setImportDirectory($a_import_dir = null): void
    {
        if (strlen($a_import_dir)) {
            ilSession::set('tst_import_dir', $a_import_dir);
        } else {
            ilSession::clear('tst_import_dir');
        }
    }

    /**
    * Get the import directory location of the test
    *
    * @return mixed|null The location of the import directory or false if the directory doesn't exist
    */
    public static function _getImportDirectory()
    {
        if (strlen(ilSession::get('tst_import_dir'))) {
            return ilSession::get('tst_import_dir');
        }
        return null;
    }

    /** @return mixed|null */
    public function getImportDirectory()
    {
        return ilObjTest::_getImportDirectory();
    }

    /**
    * creates data directory for import files
    * (data_dir/tst_data/tst_<id>/import, depending on data
    * directory that is set in ILIAS setup/ini)
    */
    public static function _createImportDirectory(): string
    {
        global $DIC;
        $ilias = $DIC['ilias'];
        $tst_data_dir = ilFileUtils::getDataDir() . "/tst_data";
        ilFileUtils::makeDir($tst_data_dir);

        if (!is_writable($tst_data_dir)) {
            $ilias->raiseError("Test Data Directory (" . $tst_data_dir
                . ") not writeable.", $ilias->error_obj->FATAL);
        }

        // create test directory (data_dir/tst_data/tst_import)
        $tst_dir = $tst_data_dir . "/tst_import";
        ilFileUtils::makeDir($tst_dir);
        if (!@is_dir($tst_dir)) {
            $ilias->raiseError("Creation of test import directory failed.", $ilias->error_obj->FATAL);
        }

        // assert that this is empty and does not contain old data
        ilFileUtils::delDir($tst_dir, true);

        return $tst_dir;
    }

    public function hasSingleChoiceQuestions(): bool
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT DISTINCT(qpl_qst_type.type_tag) foundtypes FROM qpl_questions, tst_test_result, qpl_qst_type, tst_active WHERE tst_test_result.question_fi = qpl_questions.question_id AND qpl_questions.question_type_fi = qpl_qst_type.question_type_id AND tst_test_result.active_fi = tst_active.active_id AND tst_active.test_fi = %s",
            array('integer'),
            array($this->getTestId())
        );
        $hasSC = false;
        while ($row = $ilDB->fetchAssoc($result)) {
            if (strcmp($row['foundtypes'], 'assSingleChoice') == 0) {
                $hasSC = true;
            }
        }
        return $hasSC;
    }

    public function isSingleChoiceTest(): bool
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT DISTINCT(qpl_qst_type.type_tag) foundtypes FROM qpl_questions, tst_test_result, qpl_qst_type, tst_active WHERE tst_test_result.question_fi = qpl_questions.question_id AND qpl_questions.question_type_fi = qpl_qst_type.question_type_id AND tst_test_result.active_fi = tst_active.active_id AND tst_active.test_fi = %s",
            array('integer'),
            array($this->getTestId())
        );
        if ($result->numRows() == 1) {
            $row = $ilDB->fetchAssoc($result);
            if (strcmp($row['foundtypes'], 'assSingleChoice') == 0) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    public function isSingleChoiceTestWithoutShuffle(): bool
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        if (!$this->hasSingleChoiceQuestions()) {
            return false;
        }

        $result = $ilDB->queryF(
            "
				SELECT	DISTINCT(qpl_qst_sc.shuffle) foundshuffles
				FROM	qpl_questions,
						qpl_qst_sc,
						tst_test_result,
						qpl_qst_type,
						tst_active
				WHERE	tst_test_result.question_fi = qpl_questions.question_id
				AND		qpl_questions.question_type_fi = qpl_qst_type.question_type_id
				AND		tst_test_result.active_fi = tst_active.active_id
				AND		qpl_questions.question_id = qpl_qst_sc.question_fi
				AND		tst_active.test_fi = %s
				AND		qpl_qst_type.type_tag = %s
			",
            array('integer', 'text'),
            array($this->getTestId(), 'assSingleChoice')
        );
        if ($result->numRows() == 1) {
            $row = $ilDB->fetchAssoc($result);
            return ($row['foundshuffles'] == 0);
        }
        return false;
    }

    final public function isComplete(ilTestQuestionSetConfig $testQuestionSetConfig): bool
    {
        if (!count($this->mark_schema->mark_steps)) {
            return false;
        }

        if (!$testQuestionSetConfig->isQuestionSetConfigured()) {
            return false;
        }

        return true;
    }

    public function _isComplete($obj_id): bool
    {
        global $DIC;
        $tree = $DIC['tree'];
        $ilDB = $DIC['ilDB'];
        $component_repository = $DIC['component.repository'];

        $test = new ilObjTest($obj_id, false);
        $test->loadFromDb();

        $testQuestionSetConfigFactory = new ilTestQuestionSetConfigFactory($tree, $ilDB, $component_repository, $test);

        return $test->isComplete($testQuestionSetConfigFactory->getQuestionSetConfig());
    }

    public function saveECTSStatus(): void
    {
        /**
         * @var $ilDB ilDBInterface
         */
        global $DIC;
        $ilDB = $DIC['ilDB'];

        if ($this->getTestId() > 0) {
            $grades = $this->getECTSGrades();
            $ilDB->manipulateF(
                "UPDATE tst_tests
				SET ects_output = %s, ects_a = %s, ects_b = %s, ects_c = %s, ects_d = %s, ects_e = %s, ects_fx = %s
				WHERE test_id = %s",
                array('text', 'float', 'float', 'float', 'float', 'float', 'float', 'integer'),
                array(
                    (int) $this->getECTSOutput(),
                    $grades['A'], $grades['B'], $grades['C'], $grades['D'], $grades['E'],
                    $this->getECTSFX(),
                    $this->getTestId()
                )
            );
        }
    }

    public function saveCompleteStatus(ilTestQuestionSetConfig $testQuestionSetConfig): void
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $complete = 0;
        if ($this->isComplete($testQuestionSetConfig)) {
            $complete = 1;
        }
        if ($this->getTestId() > 0) {
            $ilDB->manipulateF(
                "UPDATE tst_tests SET complete = %s WHERE test_id = %s",
                array('text', 'integer'),
                array($complete, $this->test_id)
            );
        }
    }

    /**
     * Returns the content of all RTE enabled text areas in the test
     * @return string[]
     */
    public function getAllRTEContent(): array
    {
        $result = array();
        array_push($result, $this->getIntroduction());
        array_push($result, $this->getFinalStatement());
        return $result;
    }

    /**
     * Cleans up the media objects for all text fields in a test which are using an RTE field
     */
    public function cleanupMediaobjectUsage(): void
    {
        $completecontent = "";
        foreach ($this->getAllRTEContent() as $content) {
            $completecontent .= $content;
        }
        ilRTE::_cleanupMediaObjectUsage(
            $completecontent,
            $this->getType() . ":html",
            $this->getId()
        );
    }

    public function saveToDb(bool $properties_only = false): void
    {
        global $DIC;
        $tree = $DIC['tree'];
        $ilDB = $DIC['ilDB'];
        $component_repository = $DIC['component.repository'];

        // moved online_status to ilObjectActivation (see below)

        // cleanup RTE images
        $this->cleanupMediaobjectUsage();

        $testQuestionSetConfigFactory = new ilTestQuestionSetConfigFactory($tree, $ilDB, $component_repository, $this);
        $testQuestionSetConfig = $testQuestionSetConfigFactory->getQuestionSetConfig();

        if ($this->test_id == -1) {
            // Create new dataset
            $next_id = $ilDB->nextId('tst_tests');

            $ilDB->insert('tst_tests', array(
                'test_id' => array('integer', $next_id),
                'obj_fi' => array('integer', $this->getId()),
                'author' => array('text', $this->getAuthor()),
                'intro_enabled' => array('integer', (int) $this->isIntroductionEnabled()),
                'introduction' => array('text', ilRTE::_replaceMediaObjectImageSrc((string) $this->getIntroduction(), 0)),
                'finalstatement' => array('text', ilRTE::_replaceMediaObjectImageSrc((string) $this->getFinalStatement(), 0)),
                'showinfo' => array('integer', $this->getShowInfo()),
                'forcejs' => array('integer', $this->getForceJS()),
                'customstyle' => array('text', $this->getCustomStyle()),
                'showfinalstatement' => array('integer', $this->getShowFinalStatement()),
                'sequence_settings' => array('integer', $this->getSequenceSettings()),
                'score_reporting' => array('integer', $this->getScoreReporting()),
                'instant_verification' => array('text', $this->getInstantFeedbackSolution()),
                'answer_feedback_points' => array('text', $this->getAnswerFeedbackPoints()),
                'answer_feedback' => array('text', $this->getAnswerFeedback()),
                'anonymity' => array('text', $this->getAnonymity()),
                'show_cancel' => array('text', $this->getShowCancel()),
                'show_marker' => array('integer', $this->getShowMarker()),
                'fixed_participants' => array('text', $this->getFixedParticipants()),
                'nr_of_tries' => array('integer', $this->getNrOfTries()),
                'block_after_passed' => array('integer', (int) $this->isBlockPassesAfterPassedEnabled()),
                'kiosk' => array('integer', $this->getKiosk()),
                'use_previous_answers' => array('text', $this->getUsePreviousAnswers()),
                'title_output' => array('text', $this->getTitleOutput()),
                'processing_time' => array('text', $this->getProcessingTime()),
                'enable_processing_time' => array('text', $this->getEnableProcessingTime()),
                'reset_processing_time' => array('integer', $this->getResetProcessingTime()),
                'starting_time_enabled' => array('integer', $this->isStartingTimeEnabled()),
                'starting_time' => array('integer', $this->getStartingTime()),
                'ending_time_enabled' => array('integer', $this->isEndingTimeEnabled()),
                'ending_time' => array('integer', $this->getEndingTime()),
                'complete' => array('text', $this->isComplete($testQuestionSetConfig)),
                'ects_output' => array('text', $this->getECTSOutput()),
                'ects_a' => array('float', strlen($this->ects_grades["A"]) ? $this->ects_grades["A"] : 90),// defaults as per db definition
                'ects_b' => array('float', strlen($this->ects_grades["B"]) ? $this->ects_grades["B"] : 65),
                'ects_c' => array('float', strlen($this->ects_grades["C"]) ? $this->ects_grades["C"] : 35),
                'ects_d' => array('float', strlen($this->ects_grades["D"]) ? $this->ects_grades["D"] : 10),
                'ects_e' => array('float', strlen($this->ects_grades["E"]) ? $this->ects_grades["E"] : 0),
                'ects_fx' => array('float', $this->getECTSFX()),
                'shuffle_questions' => array('text', $this->getShuffleQuestions()),
                'show_summary' => array('integer', $this->getListOfQuestionsSettings()),
                'password_enabled' => array('integer', (int) $this->isPasswordEnabled()),
                'password' => array('text', $this->getPassword()),
                'limit_users_enabled' => array('integer', (int) $this->isLimitUsersEnabled()),
                'allowedusers' => array('integer', $this->getAllowedUsers()),
                'alloweduserstimegap' => array('integer', $this->getAllowedUsersTimeGap()),
                'mailnottype' => array('integer', $this->getMailNotificationType()),
                'mailnotification' => array('integer', $this->getMailNotification()),
                'created' => array('integer', time()),
                'tstamp' => array('integer', time()),
                'enabled_view_mode' => array('text', $this->getEnabledViewMode()),
                'template_id' => array('integer', $this->getTemplate()),
                'obligations_enabled' => array('integer', (int) $this->areObligationsEnabled()),
                'offer_question_hints' => array('integer', (int) $this->isOfferingQuestionHintsEnabled()),
                'specific_feedback' => array('integer', $this->getSpecificAnswerFeedback()),
                'autosave' => array('integer', (int) $this->getAutosave()),
                'autosave_ival' => array('integer', $this->getAutosaveIval()),
                'enable_examview' => array('integer', (int) $this->getEnableExamview()),
                'show_examview_html' => array('integer', (int) $this->getShowExamviewHtml()),
                'show_examview_pdf' => array('integer', (int) $this->getShowExamviewPdf()),
                'redirection_mode' => array('integer', $this->getRedirectionMode()),
                'redirection_url' => array('text', (string) $this->getRedirectionUrl()),
                'enable_archiving' => array('integer', (int) $this->getEnableArchiving()),
                'examid_in_test_pass' => array('integer', (int) $this->isShowExamIdInTestPassEnabled()),
                'sign_submission' => array('integer', (int) $this->getSignSubmission()),
                'question_set_type' => array('text', $this->getQuestionSetType()),
                'char_selector_availability' => array('integer', $this->getCharSelectorAvailability()),
                'char_selector_definition' => array('text', (string) $this->getCharSelectorDefinition()),
                'skill_service' => array('integer', (int) $this->isSkillServiceEnabled()),
                'result_tax_filters' => array('text', serialize($this->getResultFilterTaxIds())),
                'follow_qst_answer_fixation' => array('integer', (int) $this->isFollowupQuestionAnswerFixationEnabled()),
                'inst_fb_answer_fixation' => array('integer', (int) $this->isInstantFeedbackAnswerFixationEnabled()),
                'force_inst_fb' => array('integer', (int) $this->isForceInstantFeedbackEnabled()),
                'broken' => array('integer', (int) $this->isTestFinalBroken()),
                'pass_waiting' => array('text', $this->getPassWaiting())
            ));

            $this->test_id = $next_id;

            if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
                $this->logAction($this->lng->txtlng("assessment", "log_create_new_test", ilObjAssessmentFolder::_getLogLanguage()));
            }
        } else {
            // Modify existing dataset
            $oldrow = array();
            if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
                $result = $ilDB->queryF(
                    "SELECT * FROM tst_tests WHERE test_id = %s",
                    array('integer'),
                    array($this->test_id)
                );
                if ($result->numRows() == 1) {
                    $oldrow = $ilDB->fetchAssoc($result);
                }
            }

            $ilDB->update(
                'tst_tests',
                array(
                    'author' => array('text', $this->getAuthor()),
                    'intro_enabled' => array('integer', (int) $this->isIntroductionEnabled()),
                    'introduction' => array('text', ilRTE::_replaceMediaObjectImageSrc((string) $this->getIntroduction(), 0)),
                    'finalstatement' => array('text', ilRTE::_replaceMediaObjectImageSrc((string) $this->getFinalStatement(), 0)),
                    'showinfo' => array('integer', $this->getShowInfo()),
                    'forcejs' => array('integer', $this->getForceJS()),
                    'customstyle' => array('text', $this->getCustomStyle()),
                    'showfinalstatement' => array('integer', $this->getShowFinalStatement()),
                    'sequence_settings' => array('integer', $this->getSequenceSettings()),
                    'score_reporting' => array('integer', $this->getScoreReporting()),
                    'instant_verification' => array('text', $this->getInstantFeedbackSolution()),
                    'answer_feedback_points' => array('text', $this->getAnswerFeedbackPoints()),
                    'answer_feedback' => array('text', $this->getGenericAnswerFeedback()),
                    'anonymity' => array('text', $this->getAnonymity()),
                    'show_cancel' => array('text', $this->getShowCancel()),
                    'show_marker' => array('integer', $this->getShowMarker()),
                    'fixed_participants' => array('text', $this->getFixedParticipants()),
                    'nr_of_tries' => array('integer', $this->getNrOfTries()),
                    'block_after_passed' => array('integer', (int) $this->isBlockPassesAfterPassedEnabled()),
                    'kiosk' => array('integer', $this->getKiosk()),
                    'use_previous_answers' => array('text', $this->getUsePreviousAnswers()),
                    'title_output' => array('text', $this->getTitleOutput()),
                    'processing_time' => array('text', $this->getProcessingTime()),
                    'enable_processing_time' => array('text', $this->getEnableProcessingTime()),
                    'reset_processing_time' => array('integer', $this->getResetProcessingTime()),
                    'starting_time_enabled' => array('integer', $this->isStartingTimeEnabled()),
                    'starting_time' => array('integer', $this->getStartingTime()),
                    'ending_time_enabled' => array('integer', $this->isEndingTimeEnabled()),
                    'ending_time' => array('integer', $this->getEndingTime()),
                    'complete' => array('text', $this->isComplete($testQuestionSetConfig)),
                    'ects_output' => array('text', $this->getECTSOutput()),
                    'ects_a' => array('float', strlen($this->ects_grades["A"]) ? $this->ects_grades["A"] : null),
                    'ects_b' => array('float', strlen($this->ects_grades["B"]) ? $this->ects_grades["B"] : null),
                    'ects_c' => array('float', strlen($this->ects_grades["C"]) ? $this->ects_grades["C"] : null),
                    'ects_d' => array('float', strlen($this->ects_grades["D"]) ? $this->ects_grades["D"] : null),
                    'ects_e' => array('float', strlen($this->ects_grades["E"]) ? $this->ects_grades["E"] : null),
                    'ects_fx' => array('float', $this->getECTSFX()),
                    'shuffle_questions' => array('text', $this->getShuffleQuestions()),
                    'show_summary' => array('integer', $this->getListOfQuestionsSettings()),
                    'password_enabled' => array('integer', (int) $this->isPasswordEnabled()),
                    'password' => array('text', $this->getPassword()),
                    'limit_users_enabled' => array('integer', (int) $this->isLimitUsersEnabled()),
                    'allowedusers' => array('integer', $this->getAllowedUsers()),
                    'alloweduserstimegap' => array('integer', $this->getAllowedUsersTimeGap()),
                    'mailnottype' => array('integer', $this->getMailNotificationType()),
                    'exportsettings' => array('integer', $this->getExportSettings()),
                    'mailnotification' => array('integer', $this->getMailNotification()),
                    'tstamp' => array('integer', time()),
                    'enabled_view_mode' => array('text', $this->getEnabledViewMode()),
                    'template_id' => array('integer', $this->getTemplate()),
                    'obligations_enabled' => array('integer', (int) $this->areObligationsEnabled()),
                    'offer_question_hints' => array('integer', (int) $this->isOfferingQuestionHintsEnabled()),
                    'specific_feedback' => array('integer', $this->getSpecificAnswerFeedback()),
                    'autosave' => array('integer', (int) $this->getAutosave()),
                    'autosave_ival' => array('integer', $this->getAutosaveIval()),
                    'enable_examview' => array('integer', (int) $this->getEnableExamview()),
                    'show_examview_html' => array('integer', (int) $this->getShowExamviewHtml()),
                    'show_examview_pdf' => array('integer', (int) $this->getShowExamviewPdf()),
                    'redirection_mode' => array('integer', $this->getRedirectionMode()),
                    'redirection_url' => array('text', (string) $this->getRedirectionUrl()),
                    'enable_archiving' => array('integer', (int) $this->getEnableArchiving()),
                    'examid_in_test_pass' => array('integer', (int) $this->isShowExamIdInTestPassEnabled()),
                    'sign_submission' => array('integer', (int) $this->getSignSubmission()),
                    'question_set_type' => array('text', $this->getQuestionSetType()),
                    'char_selector_availability' => array('integer', $this->getCharSelectorAvailability()),
                    'char_selector_definition' => array('text', (string) $this->getCharSelectorDefinition()),
                    'skill_service' => array('integer', (int) $this->isSkillServiceEnabled()),
                    'result_tax_filters' => array('text', serialize($this->getResultFilterTaxIds())),
                    'show_grading_status' => array('integer', (int) $this->isShowGradingStatusEnabled()),
                    'show_grading_mark' => array('integer', (int) $this->isShowGradingMarkEnabled()),
                    'follow_qst_answer_fixation' => array('integer', (int) $this->isFollowupQuestionAnswerFixationEnabled()),
                    'inst_fb_answer_fixation' => array('integer', (int) $this->isInstantFeedbackAnswerFixationEnabled()),
                    'force_inst_fb' => array('integer', (int) $this->isForceInstantFeedbackEnabled()),
                    'broken' => array('integer', (int) $this->isTestFinalBroken()),
                    'pass_waiting' => array('text', $this->getPassWaiting())
                    ),
                array(
                        'test_id' => array('integer', $this->getTestId())
                    )
            );

            if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
                $logresult = $ilDB->queryF(
                    "SELECT * FROM tst_tests WHERE test_id = %s",
                    array('integer'),
                    array($this->getTestId())
                );
                $newrow = array();
                if ($logresult->numRows() == 1) {
                    $newrow = $ilDB->fetchAssoc($logresult);
                }
                $changed_fields = array();
                foreach ($oldrow as $key => $value) {
                    if (strcmp($oldrow[$key], $newrow[$key]) != 0) {
                        array_push($changed_fields, "$key: " . $oldrow[$key] . " => " . $newrow[$key]);
                    }
                }
                $changes = join(", ", $changed_fields);
                if (count($changed_fields) > 0) {
                    $this->logAction($this->lng->txtlng("assessment", "log_modified_test", ilObjAssessmentFolder::_getLogLanguage()) . " [" . $changes . "]");
                }
            }
            if ($this->evalTotalPersons() > 0) {
                // reset the finished status of participants if the nr of test passes did change
                if ($this->getNrOfTries() > 0) {
                    // set all unfinished tests with nr of passes >= allowed passes finished
                    $aresult = $ilDB->queryF(
                        "SELECT active_id FROM tst_active WHERE test_fi = %s AND tries >= %s AND submitted = %s",
                        array('integer', 'integer', 'integer'),
                        array($this->getTestId(), $this->getNrOfTries(), 0)
                    );
                    while ($row = $ilDB->fetchAssoc($aresult)) {
                        $ilDB->manipulateF(
                            "UPDATE tst_active SET submitted = %s, submittimestamp = %s WHERE active_id = %s",
                            array('integer', 'timestamp', 'integer'),
                            array(1, date('Y-m-d H:i:s'), $row["active_id"])
                        );
                    }

                    // set all finished tests with nr of passes < allowed passes not finished
                    $aresult = $ilDB->queryF(
                        "SELECT active_id FROM tst_active WHERE test_fi = %s AND tries < %s AND submitted = %s",
                        array('integer', 'integer', 'integer'),
                        array($this->getTestId(), $this->getNrOfTries() - 1, 1)
                    );
                    while ($row = $ilDB->fetchAssoc($aresult)) {
                        $ilDB->manipulateF(
                            "UPDATE tst_active SET submitted = %s, submittimestamp = %s WHERE active_id = %s",
                            array('integer', 'timestamp', 'integer'),
                            array(0, null, $row["active_id"])
                        );
                    }
                } else {
                    // set all finished tests with nr of passes >= allowed passes not finished
                    $aresult = $ilDB->queryF(
                        "SELECT active_id FROM tst_active WHERE test_fi = %s AND submitted = %s",
                        array('integer', 'integer'),
                        array($this->getTestId(), 1)
                    );
                    while ($row = $ilDB->fetchAssoc($aresult)) {
                        $ilDB->manipulateF(
                            "UPDATE tst_active SET submitted = %s, submittimestamp = %s WHERE active_id = %s",
                            array('integer', 'timestamp', 'integer'),
                            array(0, null, $row["active_id"])
                        );
                    }
                }
            }
        }

        if (!$this->getOldOnlineStatus() && !$this->getOfflineStatus()) {
            global $DIC;
            $ilUser = $DIC['ilUser'];
            $newsItem = new ilNewsItem();
            $newsItem->setContext($this->getId(), 'tst');
            $newsItem->setPriority(NEWS_NOTICE);
            $newsItem->setTitle('new_test_online');
            $newsItem->setContentIsLangVar(true);
            $newsItem->setContent('');
            $newsItem->setUserId($ilUser->getId());
            $newsItem->setVisibility(NEWS_USERS);
            $newsItem->create();
        } elseif ($this->getOldOnlineStatus() && !$this->getOfflineStatus()) {
            ilNewsItem::deleteNewsOfContext($this->getId(), 'tst');
        } elseif (!$this->getOfflineStatus()) {
            $newsId = ilNewsItem::getFirstNewsIdForContext($this->getId(), 'tst');
            if ($newsId > 0) {
                $newsItem = new ilNewsItem($newsId);
                $newsItem->setTitle('new_test_online');
                $newsItem->setContentIsLangVar(true);
                $newsItem->setContent('');
                $newsItem->update();
            }
        }

        // moved activation to ilObjectActivation
        if ($this->ref_id) {
            ilObjectActivation::getItem($this->ref_id);

            $item = new ilObjectActivation();
            if (!$this->isActivationLimited()) {
                $item->setTimingType(ilObjectActivation::TIMINGS_DEACTIVATED);
            } else {
                $item->setTimingType(ilObjectActivation::TIMINGS_ACTIVATION);
                $item->setTimingStart($this->getActivationStartingTime());
                $item->setTimingEnd($this->getActivationEndingTime());
                $item->toggleVisible($this->getActivationVisibility());
            }

            $item->update($this->ref_id);
        }

        if (!$properties_only) {
            if ($this->getQuestionSetType() == self::QUESTION_SET_TYPE_FIXED) {
                $this->saveQuestionsToDb();
            }

            $this->mark_schema->saveToDb($this->test_id);
        }
    }

    public function saveQuestionsToDb(): void
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $oldquestions = array();
        if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
            $result = $ilDB->queryF(
                "SELECT question_fi FROM tst_test_question WHERE test_fi = %s ORDER BY sequence",
                array('integer'),
                array($this->getTestId())
            );
            if ($result->numRows() > 0) {
                while ($row = $ilDB->fetchAssoc($result)) {
                    array_push($oldquestions, $row["question_fi"]);
                }
            }
        }
        // workaround for lost obligations
        // this method is called if a question is removed
        $currentQuestionsObligationsQuery = 'SELECT question_fi, obligatory FROM tst_test_question WHERE test_fi = %s';
        $rset = $ilDB->queryF($currentQuestionsObligationsQuery, array('integer'), array($this->getTestId()));
        while ($row = $ilDB->fetchAssoc($rset)) {
            $obligatoryQuestionState[$row['question_fi']] = $row['obligatory'];
        }
        // delete existing category relations
        $affectedRows = $ilDB->manipulateF(
            "DELETE FROM tst_test_question WHERE test_fi = %s",
            array('integer'),
            array($this->getTestId())
        );
        // create new category relations
        foreach ($this->questions as $key => $value) {
            // workaround for import witout obligations information
            if (!isset($obligatoryQuestionState[$value]) || is_null($obligatoryQuestionState[$value])) {
                $obligatoryQuestionState[$value] = 0;
            }

            // insert question
            $next_id = $ilDB->nextId('tst_test_question');
            $ilDB->insert('tst_test_question', array(
                'test_question_id' => array('integer', $next_id),
                'test_fi' => array('integer', $this->getTestId()),
                'question_fi' => array('integer', $value),
                'sequence' => array('integer', $key),
                'obligatory' => array('integer', $obligatoryQuestionState[$value]),
                'tstamp' => array('integer', time())
            ));
        }
        if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
            $result = $ilDB->queryF(
                "SELECT question_fi FROM tst_test_question WHERE test_fi = %s ORDER BY sequence",
                array('integer'),
                array($this->getTestId())
            );
            $newquestions = array();
            if ($result->numRows() > 0) {
                while ($row = $ilDB->fetchAssoc($result)) {
                    array_push($newquestions, $row["question_fi"]);
                }
            }
            foreach ($oldquestions as $index => $question_id) {
                if (strcmp($newquestions[$index], $question_id) != 0) {
                    $pos = array_search($question_id, $newquestions);
                    if ($pos === false) {
                        $this->logAction($this->lng->txtlng("assessment", "log_question_removed", ilObjAssessmentFolder::_getLogLanguage()), $question_id);
                    } else {
                        $this->logAction($this->lng->txtlng("assessment", "log_question_position_changed", ilObjAssessmentFolder::_getLogLanguage()) . ": " . ($index + 1) . " => " . ($pos + 1), $question_id);
                    }
                }
            }
            foreach ($newquestions as $index => $question_id) {
                if (array_search($question_id, $oldquestions) === false) {
                    $this->logAction($this->lng->txtlng("assessment", "log_question_added", ilObjAssessmentFolder::_getLogLanguage()) . ": " . ($index + 1), $question_id);
                }
            }
        }
    }

    /**
     * Checks wheather the test is a new random test (using tst_rnd_cpy) or an old one
     *
     * @deprecated --> old school random test
     */
    protected function isNewRandomTest(): bool
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $result = $ilDB->queryF(
            'SELECT copy_id FROM tst_rnd_cpy WHERE tst_fi = %s',
            array('integer'),
            array($this->getTestId())
        );
        return $result->numRows() > 0;
    }

    /**
     * Returns a random selection of questions
     *
     * @param bool|int $use_obj_id Use the object id instead of the reference id when set to true
     * @param array|string $qpls An array of questionpool id's if the random questions should only be chose from the contained questionpools
     * @deprecated --> old school random test
     */
    public function randomSelectQuestions(
        int $nr_of_questions,
        int $questionpool,
        $use_obj_id = 0,
        $qpls = "",
        $pass = null
    ): array {
        global $DIC;
        $rbacsystem = $DIC['rbacsystem'];
        $ilDB = $DIC['ilDB'];

        // retrieve object id instead of ref id if necessary
        if (($questionpool != 0) && (!$use_obj_id)) {
            $questionpool = ilObject::_lookupObjId($questionpool);
        }

        // get original ids of all existing questions in the test
        $result = $ilDB->queryF(
            "SELECT qpl_questions.original_id FROM qpl_questions, tst_test_question WHERE qpl_questions.question_id = tst_test_question.question_fi AND qpl_questions.tstamp > 0 AND tst_test_question.test_fi = %s",
            array("integer"),
            array($this->getTestId())
        );
        $original_ids = array();
        $paramtypes = array();
        $paramvalues = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            array_push($original_ids, $row['original_id']);
        }

        $available = "";
        // get a list of all available questionpools
        if (($questionpool == 0) && (!is_array($qpls))) {
            $available_pools = array_keys(ilObjQuestionPool::_getAvailableQuestionpools($use_object_id = true, $equal_points = false, $could_be_offline = false, $showPath = false, $with_questioncount = false, "read", ilObject::_lookupOwner($this->getId())));
            if (count($available_pools)) {
                $available = " AND " . $ilDB->in('obj_fi', $available_pools, false, 'integer');
            } else {
                return array();
            }
        }

        $constraint_qpls = "";
        $result_array = array();
        if ($questionpool == 0) {
            if (is_array($qpls)) {
                if (count($qpls) > 0) {
                    $constraint_qpls = " AND " . $ilDB->in('obj_fi', $qpls, false, 'integer');
                }
            }
        }

        $original_clause = "";
        if (count($original_ids)) {
            $original_clause = " AND " . $ilDB->in('question_id', $original_ids, true, 'integer');
        }

        if ($questionpool == 0) {
            $result = $ilDB->queryF(
                "SELECT question_id FROM qpl_questions WHERE original_id IS NULL $available $constraint_qpls AND owner > %s AND complete = %s $original_clause",
                array('integer', 'text'),
                array(0, "1")
            );
        } else {
            $result = $ilDB->queryF(
                "SELECT question_id FROM qpl_questions WHERE original_id IS NULL AND obj_fi = %s AND owner > %s AND complete = %s $original_clause",
                array('integer','integer', 'text'),
                array($questionpool, 0, "1")
            );
        }
        $found_ids = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            array_push($found_ids, $row['question_id']);
        }
        $nr_of_questions = ($nr_of_questions > count($found_ids)) ? count($found_ids) : $nr_of_questions;
        if ($nr_of_questions == 0) {
            return array();
        }
        $rand_keys = array_rand($found_ids, $nr_of_questions);
        $result = array();
        if (is_array($rand_keys)) {
            foreach ($rand_keys as $key) {
                $result[$found_ids[$key]] = $found_ids[$key];
            }
        } else {
            $result[$found_ids[$rand_keys]] = $found_ids[$rand_keys];
        }
        return $result;
    }

    /**
     * Calculates the number of user results for a specific test pass
     */
    public function getNrOfResultsForPass($active_id, $pass): int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT test_result_id FROM tst_test_result WHERE active_fi = %s AND pass = %s",
            array('integer','integer'),
            array($active_id, $pass)
        );
        return $result->numRows();
    }

    /**
     * Checkes wheather a random test has already created questions for a given pass or not
     * @deprecated: still in use?
     */
    public function hasRandomQuestionsForPass(int $active_id, int $pass): bool
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $result = $ilDB->queryF(
            "SELECT test_random_question_id FROM tst_test_rnd_qst WHERE active_fi = %s AND pass = %s",
            array('integer','integer'),
            array($active_id, $pass)
        );
        return ($result->numRows() > 0) ? true : false;
    }

    public function loadFromDb(): void
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT * FROM tst_tests WHERE obj_fi = %s",
            array('integer'),
            array($this->getId())
        );
        if ($result->numRows() == 1) {
            $data = $ilDB->fetchObject($result);
            $this->setTestId($data->test_id);
            if (strlen($this->getAuthor()) == 0) {
                $this->saveAuthorToMetadata($data->author);
            }
            $this->setAuthor($data->author);
            $this->setIntroductionEnabled($data->intro_enabled);
            $this->setIntroduction(ilRTE::_replaceMediaObjectImageSrc((string) $data->introduction, 1));
            $this->setShowInfo($data->showinfo);
            $this->setFinalStatement(ilRTE::_replaceMediaObjectImageSrc((string) $data->finalstatement, 1));
            $this->setForceJS($data->forcejs);
            $this->setCustomStyle($data->customstyle);
            $this->setShowFinalStatement($data->showfinalstatement);
            $this->setSequenceSettings($data->sequence_settings);
            $this->setScoreReporting($data->score_reporting);
            $this->setInstantFeedbackSolution($data->instant_verification);
            $this->setAnswerFeedbackPoints($data->answer_feedback_points);
            $this->setAnswerFeedback($data->answer_feedback);
            $this->setAnonymity($data->anonymity);
            $this->setShowCancel($data->show_cancel);
            $this->setShowMarker($data->show_marker);
            $this->setFixedParticipants($data->fixed_participants);
            $this->setNrOfTries($data->nr_of_tries);
            $this->setBlockPassesAfterPassedEnabled((bool) $data->block_after_passed);
            $this->setKiosk($data->kiosk);
            $this->setUsePreviousAnswers($data->use_previous_answers);
            $this->setRedirectionMode($data->redirection_mode);
            $this->setRedirectionUrl($data->redirection_url);
            $this->setTitleOutput($data->title_output);
            $this->setProcessingTime($data->processing_time);
            $this->setEnableProcessingTime($data->enable_processing_time);
            $this->setResetProcessingTime($data->reset_processing_time);
            $this->setReportingDate($data->reporting_date);
            $this->setShuffleQuestions($data->shuffle_questions);
            $this->setResultsPresentation($data->results_presentation);
            $this->setStartingTimeEnabled($data->starting_time_enabled);
            $this->setStartingTime($data->starting_time);
            $this->setEndingTimeEnabled($data->ending_time_enabled);
            $this->setEndingTime($data->ending_time);
            $this->setListOfQuestionsSettings($data->show_summary);
            $this->setECTSOutput($data->ects_output);
            $this->setECTSGrades(
                array(
                    "A" => $data->ects_a,
                    "B" => $data->ects_b,
                    "C" => $data->ects_c,
                    "D" => $data->ects_d,
                    "E" => $data->ects_e
                )
            );
            $this->setECTSFX($data->ects_fx);
            $this->mark_schema->flush();
            $this->mark_schema->loadFromDb($this->getTestId());
            $this->setMailNotification($data->mailnotification);
            $this->setMailNotificationType($data->mailnottype);
            $this->setPasswordEnabled($data->password_enabled);
            $this->setPassword($data->password);
            $this->setLimitUsersEnabled($data->limit_users_enabled);
            $this->setAllowedUsers($data->allowedusers);
            $this->setAllowedUsersTimeGap($data->alloweduserstimegap);
            $this->setObligationsEnabled($data->obligations_enabled);
            $this->setOfferingQuestionHintsEnabled($data->offer_question_hints);
            $this->setEnabledViewMode($data->enabled_view_mode);
            $this->setTemplate($data->template_id);
            $this->setOldOnlineStatus(!$this->getOfflineStatus());
            $this->setSpecificAnswerFeedback((int) $data->specific_feedback);
            $this->setAutosave((bool) $data->autosave);
            $this->setAutosaveIval((int) $data->autosave_ival);
            $this->setEnableExamview((bool) $data->enable_examview);
            $this->setShowExamviewHtml((bool) $data->show_examview_html);
            $this->setShowExamviewPdf((bool) $data->show_examview_pdf);
            $this->setEnableArchiving((bool) $data->enable_archiving);
            $this->setShowExamIdInTestPassEnabled((bool) $data->examid_in_test_pass);
            $this->setSignSubmission((bool) $data->sign_submission);
            $this->setQuestionSetType($data->question_set_type);
            $this->setCharSelectorAvailability((int) $data->char_selector_availability);
            $this->setCharSelectorDefinition($data->char_selector_definition);
            $this->setSkillServiceEnabled((bool) $data->skill_service);
            $this->setShowGradingStatusEnabled((bool) $data->show_grading_status);
            $this->setShowGradingMarkEnabled((bool) $data->show_grading_mark);
            $this->setFollowupQuestionAnswerFixationEnabled((bool) $data->follow_qst_answer_fixation);
            $this->setInstantFeedbackAnswerFixationEnabled((bool) $data->inst_fb_answer_fixation);
            $this->setForceInstantFeedbackEnabled((bool) $data->force_inst_fb);
            $this->setTestFinalBroken((bool) $data->broken);
            $this->setPassWaiting($data->pass_waiting);
            $this->loadQuestions();
        }

        // moved activation to ilObjectActivation
        if (isset($this->ref_id)) {
            $activation = ilObjectActivation::getItem($this->ref_id);
            switch ($activation["timing_type"]) {
                case ilObjectActivation::TIMINGS_ACTIVATION:
                    $this->setActivationLimited(true);
                    $this->setActivationStartingTime($activation["timing_start"]);
                    $this->setActivationEndingTime($activation["timing_end"]);
                    $this->setActivationVisibility($activation["visible"]);
                    break;

                default:
                    $this->setActivationLimited(false);
                    break;
            }
        }
    }

    /**
    * Load the test question id's from the database
    *
    * @param int|null|string $active_id
     *
    */
    public function loadQuestions($active_id = "", $pass = null): void
    {
        global $DIC;
        $ilUser = $DIC['ilUser'];
        $ilDB = $DIC['ilDB'];

        $this->questions = array();
        if ($this->isRandomTest()) {
            if (strcmp($active_id, "") == 0) {
                $active_id = $this->getActiveIdOfUser($ilUser->getId());
            }
            if (is_null($pass)) {
                $pass = self::_getPass($active_id);
            }
            $result = $ilDB->queryF(
                "SELECT tst_test_rnd_qst.* FROM tst_test_rnd_qst, qpl_questions WHERE tst_test_rnd_qst.active_fi = %s AND qpl_questions.question_id = tst_test_rnd_qst.question_fi AND tst_test_rnd_qst.pass = %s ORDER BY sequence",
                array('integer', 'integer'),
                array($active_id, $pass)
            );
            // The following is a fix for random tests prior to ILIAS 3.8. If someone started a random test in ILIAS < 3.8, there
            // is only one test pass (pass = 0) in tst_test_rnd_qst while with ILIAS 3.8 there are questions for every test pass.
            // To prevent problems with tests started in an older version and continued in ILIAS 3.8, the first pass should be taken if
            // no questions are present for a newer pass.
            if ($result->numRows() == 0) {
                $result = $ilDB->queryF(
                    "SELECT tst_test_rnd_qst.* FROM tst_test_rnd_qst, qpl_questions WHERE tst_test_rnd_qst.active_fi = %s AND qpl_questions.question_id = tst_test_rnd_qst.question_fi AND tst_test_rnd_qst.pass = 0 ORDER BY sequence",
                    array('integer'),
                    array($active_id)
                );
            }
        } else {
            $result = $ilDB->queryF(
                "SELECT tst_test_question.* FROM tst_test_question, qpl_questions WHERE tst_test_question.test_fi = %s AND qpl_questions.question_id = tst_test_question.question_fi ORDER BY sequence",
                array('integer'),
                array($this->test_id)
            );
        }
        $index = 1;
        if ($this->test_id !== -1) {
            //Omit loading of questions for non-id'ed test
            while ($data = $ilDB->fetchAssoc($result)) {
                $this->questions[$index++] = $data["question_fi"];
            }
        }
    }

    public function isIntroductionEnabled(): bool
    {
        return $this->introductionEnabled;
    }

    /**
     * @param bool|string $introductionEnabled
     */
    public function setIntroductionEnabled($introductionEnabled): void
    {
        $this->introductionEnabled = $introductionEnabled;
    }

    public function getIntroduction(): string
    {
        return $this->introduction;
    }

    public function setIntroduction(string $introduction): void
    {
        $this->introduction = $this->getHtmlQuestionContentPurifier()->purify($introduction);
    }

    public function setFinalStatement(string $a_statement): void
    {
        $this->_finalstatement = $a_statement;
    }

    /**
    * Set whether the complete information page is shown or the required data only
    *
    * @param integer $a_info 1 for the complete information, 0 otherwise
    * @access public
    * @see $_showinfo
    */
    public function setShowInfo($a_info = 1)
    {
        $this->_showinfo = ($a_info) ? 1 : 0;
    }

    /**
    * Set whether JavaScript should be forced for tests
    *
    * @param integer $a_js 1 to force JavaScript, 0 otherwise
    * @access public
    * @see $_forcejs
    */
    public function setForceJS($a_js = 1)
    {
        $this->_forcejs = ($a_js) ? 1 : 0;
    }

    /**
    * Set the custom style
    *
    * @param string $a_customStyle The custom style
    * @access public
    * @see $_customStyle
    */
    public function setCustomStyle($a_customStyle = null)
    {
        $this->_customStyle = $a_customStyle;
    }

    /**
    * @return string|null The custom style, NULL if empty
    * @see $_customStyle
    */
    public function getCustomStyle(): ?string
    {
        return (strlen($this->_customStyle)) ? $this->_customStyle : null;
    }

    /**
    * Sets whether the final statement should be shown or not
    *
    * @param integer $show 1 if TRUE or 0 if FALSE
    * @access public
    * @see $_finalstatement
    */
    public function setShowFinalStatement($show = 0)
    {
        $this->_showfinalstatement = ($show) ? 1 : 0;
    }

    public function getFinalStatement(): string
    {
        return $this->_finalstatement;
    }

    /**
    * Gets whether the complete information page is shown or the required data only
    *
    * @return integer 1 for the complete information, 0 otherwise
    * @access public
    * @see $_showinfo
    */
    public function getShowInfo(): int
    {
        return ($this->_showinfo) ? 1 : 0;
    }

    /**
    * Gets whether JavaScript should be forced for tests
    *
    * @return integer 1 to force JavaScript, 0 otherwise
    * @access public
    * @see $_forcejs
    */
    public function getForceJS(): int
    {
        return ($this->_forcejs) ? 1 : 0;
    }

    /**
    * Returns whether the final statement should be shown or not
    *
    * @return integer 0 if false, 1 if true
    * @access public
    * @see $_showfinalstatement
    */
    public function getShowFinalStatement(): int
    {
        return ($this->_showfinalstatement) ? 1 : 0;
    }

    /**
    * Gets the database id of the additional test data
    *
    * @return integer The database id of the additional test data
    * @access public
    * @see $test_id
    */
    public function getTestId(): int
    {
        return $this->test_id;
    }

    public function getECTSOutput(): int
    {
        return ($this->ects_output) ? 1 : 0;
    }

    public function setECTSOutput($a_ects_output): void
    {
        $this->ects_output = $a_ects_output ? 1 : 0;
    }

    public function getECTSFX(): ?float
    {
        return $this->ects_fx;
    }

    public function setECTSFX($a_ects_fx): void
    {
        $this->ects_fx = (float) str_replace(",", ".", $a_ects_fx);
    }

    public function getECTSGrades(): array
    {
        return $this->ects_grades;
    }

    public function setECTSGrades(array $a_ects_grades): void
    {
        $this->ects_grades = $a_ects_grades;
    }

    /**
     * SEQUENCE SETTING = POSTPONING ENABLED !!
     *
     * @return integer The POSTPONING ENABLED status
     */
    public function getSequenceSettings(): int
    {
        return ($this->sequence_settings) ? $this->sequence_settings : 0;
    }

    /**
     * SEQUENCE SETTING = POSTPONING ENABLED !!
     *
     * @param int|string $sequence_settings The POSTPONING ENABLED status
     */
    public function setSequenceSettings($sequence_settings = 0): void
    {
        $this->sequence_settings = $sequence_settings;
    }

    public function isPostponingEnabled(): bool
    {
        return (bool) $this->getSequenceSettings();
    }

    public function setPostponingEnabled($postponingEnabled): void
    {
        $this->setSequenceSettings((int) $postponingEnabled);
    }

    /**
     * Sets the score reporting of the ilObjTest object
     *
     * @param int|string $score_reporting The score reporting
     * @see $score_reporting
     * @deprecated
     */
    public function setScoreReporting($score_reporting = 0): void
    {
        $this->score_reporting = (int) $score_reporting;
    }

    /**
    * Sets the instant feedback for the solution
    *
    * @param int|string $instant_feedback If 1, the solution will be shown after answering a question
    * @see $instant_verification
    */
    public function setInstantFeedbackSolution($instant_feedback = 0): void
    {
        switch ($instant_feedback) {
            case 1:
                $this->instant_verification = 1;
                break;
            default:
                $this->instant_verification = 0;
                break;
        }
    }

    /**
    * Sets the generic feedback for the test
    * @deprecate Use setGenericAnswerFeedback instead.
    * @param int|string $answer_feedback If 1, answer specific feedback will be shown after answering a question
    * @see $answer_feedback
    */
    public function setAnswerFeedback($answer_feedback = 0): void
    {
        switch ($answer_feedback) {
            case 1:
                $this->answer_feedback = 1;
                break;
            default:
                $this->answer_feedback = 0;
                break;
        }
    }

    public function setGenericAnswerFeedback(int $generic_answer_feedback = 0): void
    {
        switch ($generic_answer_feedback) {
            case 1:
                $this->answer_feedback = 1;
                break;
            default:
                $this->answer_feedback = 0;
                break;
        }
    }

    /**
    * Sets the answer specific feedback of reached points for the test
    *
    * @param int|string $answer_feedback_points If 1, answer specific feedback will show the reached points after answering a question
    * @see $answer_feedback_points
    */
    public function setAnswerFeedbackPoints($answer_feedback_points = 0): void
    {
        switch ($answer_feedback_points) {
            case 1:
                $this->answer_feedback_points = 1;
                break;
            default:
                $this->answer_feedback_points = 0;
                break;
        }
    }

    /**
     * Sets the reporting date of the ilObjTest object when the score reporting is available
     *
     */
    public function setReportingDate($reporting_date): void
    {
        if (!$reporting_date) {
            $this->reporting_date = '';
            $this->setECTSOutput(false);
        } else {
            $this->reporting_date = $reporting_date;
        }
    }

    public const SCORE_REPORTING_DISABLED = 0;
    public const SCORE_REPORTING_FINISHED = 1;
    public const SCORE_REPORTING_IMMIDIATLY = 2;
    public const SCORE_REPORTING_DATE = 3;
    public const SCORE_REPORTING_AFTER_PASSED = 4;

    /**
    * Gets the score reporting of the ilObjTest object
    *
    * @return integer The score reporting of the test
    * @access public
    * @see $score_reporting
    */
    public function getScoreReporting(): int
    {
        return ($this->score_reporting) ? $this->score_reporting : 0;
    }

    public function isScoreReportingEnabled(): bool
    {
        switch ($this->getScoreReporting()) {
            case self::SCORE_REPORTING_FINISHED:
            case self::SCORE_REPORTING_IMMIDIATLY:
            case self::SCORE_REPORTING_DATE:
            case self::SCORE_REPORTING_AFTER_PASSED:

                return true;

            case self::SCORE_REPORTING_DISABLED:
            default:

                return false;
        }
    }

    /**
    * Returns 1 if the correct solution will be shown after answering a question
    *
    * @return integer The status of the solution instant feedback
    * @access public
    * @see $instant_verification
    */
    public function getInstantFeedbackSolution(): int
    {
        return ($this->instant_verification) ? $this->instant_verification : 0;
    }

    /**
     * Returns 1 if generic answer feedback is activated
     *
     * @deprecated Use getGenericAnswerFeedback instead.
     * @return integer The status of the answer specific feedback
     * @access     public
     * @see        $answer_feedback
     */
    public function getAnswerFeedback(): int
    {
        return ($this->answer_feedback) ? $this->answer_feedback : 0;
    }

    /**
     * Returns 1 if generic answer feedback is to be shown.
     *
     * @return integer 1, if answer specific feedback is to be shown.
     * @access public
     */
    public function getGenericAnswerFeedback(): int
    {
        return ($this->answer_feedback) ? $this->answer_feedback : 0;
    }

    /**
    * Returns 1 if answer specific feedback as reached points is activated
    *
    * @return integer The status of the answer specific feedback as reached points
    * @access public
    * @see $answer_feedback_points
    */
    public function getAnswerFeedbackPoints(): int
    {
        return ($this->answer_feedback_points) ? $this->answer_feedback_points : 0;
    }

    /**
    * Gets the count system for the calculation of points
    *
    * @return integer The count system for the calculation of points
    * @access public
    * @see $count_system
    */
    public function getCountSystem(): int
    {
        return $this->getScoreSettings()->getScoringSettings()->getCountSystem();
    }

    /**
    * Gets the count system for the calculation of points
    *
    * @return integer The count system for the calculation of points
    * @access public
    * @see $count_system
    */
    public static function _getCountSystem($active_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $result = $ilDB->queryF(
            "SELECT tst_tests.count_system FROM tst_tests, tst_active WHERE tst_active.active_id = %s AND tst_active.test_fi = tst_tests.test_id",
            array('integer'),
            array($active_id)
        );
        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            return $row["count_system"];
        }
        return false;
    }

    /**
    * Determines if the score of a question should be cut at 0 points or the score of the whole test
    *
    * @return integer The score cutting type. 0 for question cutting, 1 for test cutting
    * @access public
    * @see $score_cutting
    */
    public function getScoreCutting(): int
    {
        return $this->getScoreSettings()->getScoringSettings()->getScoreCutting();
    }

    /**
    * Gets the pass scoring type
    *
    * @return integer The pass scoring type
    * @access public
    * @see $pass_scoring
    */
    public function getPassScoring(): int
    {
        return $this->getScoreSettings()->getScoringSettings()->getPassScoring();
    }

    /**
    * Gets the pass scoring type
    *
    * @return integer The pass scoring type
    * @access public
    * @see $pass_scoring
    */
    public static function _getPassScoring($active_id): int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $result = $ilDB->queryF(
            "SELECT tst_tests.pass_scoring FROM tst_tests, tst_active WHERE tst_tests.test_id = tst_active.test_fi AND tst_active.active_id = %s",
            array('integer'),
            array($active_id)
        );
        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            return $row["pass_scoring"];
        }
        return 0;
    }

    /**
    * Determines if the score of a question should be cut at 0 points or the score of the whole test
    *
    * @return boolean The score cutting type. 0 for question cutting, 1 for test cutting
    * @access public
    * @see $score_cutting
    */
    public static function _getScoreCutting($active_id): bool
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $result = $ilDB->queryF(
            "SELECT tst_tests.score_cutting FROM tst_tests, tst_active WHERE tst_active.active_id = %s AND tst_tests.test_id = tst_active.test_fi",
            array('integer'),
            array($active_id)
        );
        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            return $row["score_cutting"];
        }
        return false;
    }

    /**
    * Gets the reporting date of the ilObjTest object
    *
    * @return string The reporting date of the test of an empty string (=FALSE) if no reporting date is set
    * @access public
    * @see $reporting_date
    */
    public function getReportingDate(): ?string
    {
        $date = $this->getScoreSettings()->getResultSummarySettings()->getReportingDate();
        if ($date) {
            $date = $date->format('YmdHis'); //legacy-reasons ;(
        }
        return $date;
    }

    /**
    * Returns the nr of tries for the test
    *
    * @return integer The maximum number of tries
    * @access public
    * @see $nr_of_tries
    */
    public function getNrOfTries(): int
    {
        return ($this->nr_of_tries) ? $this->nr_of_tries : 0;
    }

    /**
     * @return bool
     */
    public function isBlockPassesAfterPassedEnabled(): bool
    {
        return $this->blockPassesAfterPassedEnabled;
    }

    /**
     * @param bool $blockPassesAfterPassedEnabled
     */
    public function setBlockPassesAfterPassedEnabled($blockPassesAfterPassedEnabled)
    {
        $this->blockPassesAfterPassedEnabled = $blockPassesAfterPassedEnabled;
    }

    /**
    * Returns the kiosk mode
    *
    * @return integer Kiosk mode
    * @access public
    * @see $_kiosk
    */
    public function getKiosk(): int
    {
        return ($this->_kiosk) ? $this->_kiosk : 0;
    }


    /**
    * Sets the kiosk mode for the test
    *
    * @param integer $kiosk The value for the kiosk mode.
    * @access public
    * @see $_kiosk
    */
    public function setKiosk($kiosk = 0)
    {
        $this->_kiosk = $kiosk;
    }

    /**
    * Returns the kiosk mode
    *
    * @return boolean Kiosk mode
    * @access public
    * @see $_kiosk
    */
    public function getKioskMode(): bool
    {
        if (($this->_kiosk & 1) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Sets the kiosk mode for the test
    *
    * @param boolean $kiosk The value for the kiosk mode
    * @access public
    * @see $_kiosk
    */
    public function setKioskMode($a_kiosk = false)
    {
        if ($a_kiosk) {
            $this->_kiosk = $this->_kiosk | 1;
        } else {
            if ($this->getKioskMode()) {
                $this->_kiosk = $this->_kiosk ^ 1;
            }
        }
    }

    /**
    * Returns the status of the kiosk mode title
    *
    * @return boolean Kiosk mode title
    * @access public
    * @see $_kiosk
    */
    public function getShowKioskModeTitle(): bool
    {
        if (($this->_kiosk & 2) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Set to true, if the full test title should be shown in kiosk mode
    *
    * @param boolean $a_title TRUE if the test title should be shown in kiosk mode, FALSE otherwise
    * @access public
    */
    public function setShowKioskModeTitle($a_title = false)
    {
        if ($a_title) {
            $this->_kiosk = $this->_kiosk | 2;
        } else {
            if ($this->getShowKioskModeTitle()) {
                $this->_kiosk = $this->_kiosk ^ 2;
            }
        }
    }

    /**
    * Returns the status of the kiosk mode participant
    *
    * @return boolean Kiosk mode participant
    * @access public
    * @see $_kiosk
    */
    public function getShowKioskModeParticipant(): bool
    {
        if (($this->_kiosk & 4) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Set to true, if the participant's name should be shown in kiosk mode
    *
    * @param boolean $a_title TRUE if the participant's name should be shown in kiosk mode, FALSE otherwise
    * @access public
    */
    public function setShowKioskModeParticipant($a_participant = false)
    {
        if ($a_participant) {
            $this->_kiosk = $this->_kiosk | 4;
        } else {
            if ($this->getShowKioskModeParticipant()) {
                $this->_kiosk = $this->_kiosk ^ 4;
            }
        }
    }

    /**
    * Returns if the previous answers should be shown for a learner
    *
    * @return integer 1 if the previous answers should be shown, 0 otherwise
    * @access public
    * @see $use_previous_answers
    */
    public function getUsePreviousAnswers(): int
    {
        return ($this->use_previous_answers) ? $this->use_previous_answers : 0;
    }

    /**
    * Returns the value of the title_output status
    *
    * @return integer 0 for full title, 1 for title without points, 2 for no title
    * @access public
    * @see $title_output
    */
    public function getTitleOutput(): int
    {
        return ($this->title_output) ? $this->title_output : 0;
    }

    /**
    * Returns the value of the title_output status
    *
    * @param integer $active_id The active id of a user
    * @return integer 0 for full title, 1 for title without points, 2 for no title
    * @access public
    * @see $title_output
    */
    public function _getTitleOutput($active_id): int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT tst_tests.title_output FROM tst_tests, tst_active WHERE tst_tests.test_id = tst_active.test_fi AND tst_active.active_id = %s",
            array('integer'),
            array($active_id)
        );
        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            return $row["title_output"];
        }
        return 0;
    }

    public function isPreviousSolutionReuseEnabled($active_id): bool
    {
        $result = $this->db->queryF(
            "SELECT tst_tests.use_previous_answers FROM tst_tests, tst_active WHERE tst_tests.test_id = tst_active.test_fi AND tst_active.active_id = %s",
            array("integer"),
            array($active_id)
        );
        if ($result->numRows()) {
            $row = $this->db->fetchAssoc($result);
            $test_allows_reuse = $row["use_previous_answers"];
        }

        if ($test_allows_reuse === '1') {
            $res = $this->user->getPref("tst_use_previous_answers");
            if ($res === '1') {
                return true;
            }
        }
        return false;
    }

    /**
    * Returns the processing time for the test
    *
    * @return string The processing time for the test
    * @access public
    * @see $processing_time
    */
    public function getProcessingTime()
    {
        return (strlen($this->processing_time)) ? $this->processing_time : null;
    }

    /**
    * @see $processing_time
    */
    public function getProcessingTimeAsArray(): array
    {
        if (strlen($this->processing_time)) {
            if (preg_match("/(\d{2}):(\d{2}):(\d{2})/is", $this->processing_time, $matches)) {
                if ((int) $matches[1] + (int) $matches[2] + (int) $matches[3] == 0) {
                    return $this->getEstimatedWorkingTime();
                } else {
                    return array(
                        'hh' => $matches[1],
                        'mm' => $matches[2],
                        'ss' => $matches[3],
                    );
                }
            }
        }
        return $this->getEstimatedWorkingTime();
    }

    public function getProcessingTimeAsMinutes()
    {
        if (strlen($this->processing_time)) {
            if (preg_match("/(\d{2}):(\d{2}):(\d{2})/is", $this->processing_time, $matches)) {
                return ($matches[1] * 60) + $matches[2];
            }
        }

        return self::DEFAULT_PROCESSING_TIME_MINUTES;
    }

    /**
    * Returns the processing time for the test in seconds
    *
    * @return integer The processing time for the test in seconds
    * @access public
    * @see $processing_time
    */
    public function getProcessingTimeInSeconds($active_id = ""): int
    {
        if (preg_match("/(\d{2}):(\d{2}):(\d{2})/", $this->getProcessingTime(), $matches)) {
            $extratime = $this->getExtraTime($active_id) * 60;
            return ($matches[1] * 3600) + ($matches[2] * 60) + $matches[3] + $extratime;
        } else {
            return 0;
        }
    }

    /**
    * Returns the seconds left from the actual time until the ending time
    *
    * @return integer The seconds left until the ending time is reached
    * @access public
    * @see $ending_time
    */
    public function getSecondsUntilEndingTime()
    {
        if ($this->getEndingTime() != 0) {
            $ending = $this->getEndingTime();
            $now = time();
            return $ending - $now;
        } else {
            return 0;
        }
    }

    /**
    * Returns the state of the processing time (enabled/disabled)
    *
    * @return integer The processing time state (0 for disabled, 1 for enabled)
    * @access public
    * @see $processing_time
    */
    public function getEnableProcessingTime()
    {
        return ($this->enable_processing_time) ? $this->enable_processing_time : 0;
    }

    /**
    * Returns wheather the processing time should be reset or not
    *
    * @return integer 0 for no reset, 1 for a reset
    * @access public
    * @see $reset_processing_time
    */
    public function getResetProcessingTime(): int
    {
        return ($this->reset_processing_time) ? $this->reset_processing_time : 0;
    }

    public function isStartingTimeEnabled(): ?bool
    {
        return $this->starting_time_enabled;
    }

    /**
     * @param boolean $starting_time_enabled
     */
    public function setStartingTimeEnabled($starting_time_enabled)
    {
        $this->starting_time_enabled = $starting_time_enabled;
    }

    /**
     * Returns the starting time of the test
     *
     * @return string The starting time of the test
     * @access public
     * @see $starting_time
     */
    public function getStartingTime()
    {
        return ($this->starting_time != 0) ? $this->starting_time : 0;
    }

    /**
     * Sets the starting time in database timestamp format for the test
     *
     * @param string $starting_time The starting time for the test. Empty string for no starting time.
     * @access public
     * @see $starting_time
     */
    public function setStartingTime($starting_time = null)
    {
        $this->starting_time = $starting_time;
    }

    public function isEndingTimeEnabled(): ?bool
    {
        return $this->ending_time_enabled;
    }

    /**
     * @param boolean $ending_time_enabled
     */
    public function setEndingTimeEnabled($ending_time_enabled)
    {
        $this->ending_time_enabled = $ending_time_enabled;
    }

    /**
     * Returns the ending time of the test
     *
     * @return string The ending time of the test
     * @access public
     * @see $ending_time
     */
    public function getEndingTime()
    {
        return ($this->ending_time != 0) ? $this->ending_time : 0;
    }

    /**
     * Sets the ending time in database timestamp format for the test
     *
     * @param string $ending_time The ending time for the test. Empty string for no ending time.
     * @access public
     * @see $ending_time
     */
    public function setEndingTime($ending_time = null)
    {
        $this->ending_time = $ending_time;
    }

    /**
    * Sets the nr of tries for the test
    *
    * @param integer $nr_of_tries The maximum number of tries for the test. 0 for infinite tries.
    * @access public
    * @see $nr_of_tries
    */
    public function setNrOfTries($nr_of_tries = 0)
    {
        $this->nr_of_tries = $nr_of_tries;
    }

    /**
    * Sets the status of the visibility of previous learner answers
    **
    * @param integer $use_previous_answers 1 if the previous answers should be shown
    * @access public
    * @see $use_previous_answers
    */
    public function setUsePreviousAnswers($use_previous_answers = 1)
    {
        if ($use_previous_answers) {
            $this->use_previous_answers = 1;
        } else {
            $this->use_previous_answers = 0;
        }
    }

    public function setRedirectionMode($redirection_mode = 0)
    {
        $this->redirection_mode = $redirection_mode;
    }
    public function getRedirectionMode(): int
    {
        return $this->redirection_mode;
    }
    public function setRedirectionUrl($redirection_url = null)
    {
        $this->redirection_url = $redirection_url;
    }
    public function getRedirectionUrl(): ?string
    {
        return $this->redirection_url;
    }

    /**
* Sets the status of the title output
**
* @param integer $title_output 0 for full title, 1 for title without points, 2 for no title
* @access public
* @see $title_output
*/
    public function setTitleOutput($title_output = 0)
    {
        switch ($title_output) {
            case 1:
                $this->title_output = 1;
                break;
            case 2:
                $this->title_output = 2;
                break;
            default:
                $this->title_output = 0;
                break;
        }
    }

    /**
    * Sets the processing time for the test
    *
    * @param string $processing_time The maximum processing time for the test given in hh:mm:ss
    * @access public
    * @see $processing_time
    */
    public function setProcessingTime($processing_time = "00:00:00")
    {
        $this->processing_time = $processing_time;
    }

    public function setProcessingTimeByMinutes($minutes)
    {
        $this->processing_time = sprintf("%02d:%02d:00", floor($minutes / 60), $minutes % 60);
    }

    /**
* Sets the processing time enabled or disabled
*
* @param integer $enable 0 to disable the processing time, 1 to enable the processing time
* @access public
* @see $processing_time
*/
    public function setEnableProcessingTime($enable = 0)
    {
        if ($enable) {
            $this->enable_processing_time = "1";
        } else {
            $this->enable_processing_time = "0";
        }
    }

    /**
    * Sets wheather the processing time should be reset or not
    *
    * @param integer $reset 1 to reset the processing time, 0 otherwise
    * @access public
    * @see $processing_time
    */
    public function setResetProcessingTime($reset = 0)
    {
        if ($reset) {
            $this->reset_processing_time = 1;
        } else {
            $this->reset_processing_time = 0;
        }
    }

    /**
     * @return boolean
     */
    public function isPasswordEnabled(): ?bool
    {
        return $this->passwordEnabled;
    }

    /**
     * @param boolean $passwordEnabled
     */
    public function setPasswordEnabled($passwordEnabled)
    {
        $this->passwordEnabled = $passwordEnabled;
    }

    public function getPassword(): ?string
    {
        return (strlen($this->password)) ? $this->password : null;
    }

    /**
     * Sets the password for test access
     *
     * @param string $a_password The password for test access
     * @access public
     * @see $password
     */
    public function setPassword($a_password = null): void
    {
        $this->password = $a_password;
    }

    /**
     * @return string
     */
    public function getPassWaiting(): string
    {
        return $this->pass_waiting ?? '';
    }

    /**
     * @param string $pass_waiting   mm:ddd:hh:ii:ss
     */
    public function setPassWaiting($pass_waiting)
    {
        $this->pass_waiting = $pass_waiting;
    }
    /**
     * @return bool
     */
    public function isPassWaitingEnabled(): bool
    {
        if (array_sum(explode(':', $this->getPassWaiting())) > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param int $questionId
     * @param array $activeIds
     * @param ilTestReindexedSequencePositionMap $reindexedSequencePositionMap
     */
    public function removeQuestionFromSequences($questionId, $activeIds, ilTestReindexedSequencePositionMap $reindexedSequencePositionMap): void
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */

        $testSequenceFactory = new ilTestSequenceFactory(
            $DIC->database(),
            $DIC->language(),
            $DIC['component.repository'],
            $this
        );

        foreach ($activeIds as $activeId) {
            $passSelector = new ilTestPassesSelector($DIC->database(), $this);
            $passSelector->setActiveId($activeId);

            foreach ($passSelector->getExistingPasses() as $pass) {
                $testSequence = $testSequenceFactory->getSequenceByActiveIdAndPass($activeId, $pass);
                $testSequence->loadFromDb();

                $testSequence->removeQuestion($questionId, $reindexedSequencePositionMap);
                $testSequence->saveToDb();
            }
        }
    }

    /**
     * @param int[] $removeQuestionIds
     */
    public function removeQuestions(array $removeQuestionIds): void
    {
        foreach ($removeQuestionIds as $value) {
            $this->removeQuestion((int) $value);
        }

        $this->reindexFixedQuestionOrdering();
    }

    public function removeQuestion(int $question_id): void
    {
        try {
            $question = self::_instanciateQuestion($question_id);
            if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
                $this->logAction(
                    $this->lng->txtlng("assessment", "log_question_removed", ilObjAssessmentFolder::_getLogLanguage()),
                    $question_id
                );
            }
            $question->delete($question_id);
        } catch (InvalidArgumentException $e) {
            $this->log->error($e->getMessage());
            $this->log->error($e->getTraceAsString());
        }
    }

    /**
     * - at the time beeing ilObjTest::removeTestResults needs to call the LP service for deletion
     * - ilTestLP calls ilObjTest::removeTestResultsByUserIds
     *
     * this method should only be used from non refactored soap context i think
     *
     * @param $userIds
     */
    public function removeTestResultsFromSoapLpAdministration($userIds)
    {
        $this->removeTestResultsByUserIds($userIds);

        global $DIC;
        $ilDB = $DIC['ilDB'];
        $lng = $DIC['lng'];

        $participantData = new ilTestParticipantData($ilDB, $lng);
        $participantData->setUserIdsFilter($userIds);
        $participantData->load($this->getTestId());

        $this->removeTestActives($participantData->getActiveIds());
    }

    public function removeTestResults(ilTestParticipantData $participantData)
    {
        if (count($participantData->getAnonymousActiveIds())) {
            $this->removeTestResultsByActiveIds($participantData->getAnonymousActiveIds());
        }

        if (count($participantData->getUserIds())) {
            /* @var ilTestLP $testLP */
            $testLP = ilObjectLP::getInstance($this->getId());
            $testLP->setTestObject($this);
            $testLP->resetLPDataForUserIds($participantData->getUserIds(), false);
        }

        if (count($participantData->getActiveIds())) {
            $this->removeTestActives($participantData->getActiveIds());
        }
    }

    public function removeTestResultsByUserIds($userIds)
    {
        $ilDB = $this->db;
        $lng = $this->lng;

        $participantData = new ilTestParticipantData($ilDB, $lng);
        $participantData->setUserIdsFilter($userIds);
        $participantData->load($this->getTestId());

        $IN_userIds = $ilDB->in('usr_id', $participantData->getUserIds(), false, 'integer');
        $ilDB->manipulateF(
            "DELETE FROM usr_pref WHERE $IN_userIds AND keyword = %s",
            array('text'),
            array("tst_password_" . $this->getTestId())
        );

        if (count($participantData->getActiveIds())) {
            $this->removeTestResultsByActiveIds($participantData->getActiveIds());
        }
    }

    public function removeTestResultsByActiveIds($activeIds)
    {
        $ilDB = $this->db;

        $IN_activeIds = $ilDB->in('active_fi', $activeIds, false, 'integer');

        $ilDB->manipulate("DELETE FROM tst_solutions WHERE $IN_activeIds");
        $ilDB->manipulate("DELETE FROM tst_qst_solved WHERE $IN_activeIds");
        $ilDB->manipulate("DELETE FROM tst_test_result WHERE $IN_activeIds");
        $ilDB->manipulate("DELETE FROM tst_pass_result WHERE $IN_activeIds");
        $ilDB->manipulate("DELETE FROM tst_result_cache WHERE $IN_activeIds");
        $ilDB->manipulate("DELETE FROM tst_sequence WHERE $IN_activeIds");
        $ilDB->manipulate("DELETE FROM tst_times WHERE $IN_activeIds");

        if ($this->isRandomTest()) {
            $ilDB->manipulate("DELETE FROM tst_test_rnd_qst WHERE $IN_activeIds");
        } elseif ($this->isDynamicTest()) {
            $ilDB->manipulate("DELETE FROM tst_seq_qst_tracking WHERE $IN_activeIds");
            $ilDB->manipulate("DELETE FROM tst_seq_qst_answstatus WHERE $IN_activeIds");
            $ilDB->manipulate("DELETE FROM tst_seq_qst_postponed WHERE $IN_activeIds");
            $ilDB->manipulate("DELETE FROM tst_seq_qst_checked WHERE $IN_activeIds");
        }

        foreach ($activeIds as $active_id) {
            // remove file uploads
            if (@is_dir(CLIENT_WEB_DIR . "/assessment/tst_" . $this->getTestId() . "/$active_id")) {
                ilFileUtils::delDir(CLIENT_WEB_DIR . "/assessment/tst_" . $this->getTestId() . "/$active_id");
            }

            if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
                $this->logAction(sprintf($this->lng->txtlng("assessment", "log_selected_user_data_removed", ilObjAssessmentFolder::_getLogLanguage()), $this->userLookupFullName($this->_getUserIdFromActiveId($active_id))));
            }
        }

        ilAssQuestionHintTracking::deleteRequestsByActiveIds($activeIds);
    }

    public function removeTestActives($activeIds)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $IN_activeIds = $ilDB->in('active_id', $activeIds, false, 'integer');
        $ilDB->manipulate("DELETE FROM tst_active WHERE $IN_activeIds");
    }

    /**
    * Moves a question up in order
    *
    * @param integer $question_id The database id of the question to be moved up
    * @access public
    * @see $test_id
    */
    public function questionMoveUp($question_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        // Move a question up in sequence
        $result = $ilDB->queryF(
            "SELECT * FROM tst_test_question WHERE test_fi=%s AND question_fi=%s",
            array('integer', 'integer'),
            array($this->getTestId(), $question_id)
        );
        $data = $ilDB->fetchObject($result);
        if ($data->sequence > 1) {
            // OK, it's not the top question, so move it up
            $result = $ilDB->queryF(
                "SELECT * FROM tst_test_question WHERE test_fi=%s AND sequence=%s",
                array('integer','integer'),
                array($this->getTestId(), $data->sequence - 1)
            );
            $data_previous = $ilDB->fetchObject($result);
            // change previous dataset
            $ilDB->manipulateF(
                "UPDATE tst_test_question SET sequence=%s WHERE test_question_id=%s",
                array('integer','integer'),
                array($data->sequence, $data_previous->test_question_id)
            );
            // move actual dataset up
            $ilDB->manipulateF(
                "UPDATE tst_test_question SET sequence=%s WHERE test_question_id=%s",
                array('integer','integer'),
                array($data->sequence - 1, $data->test_question_id)
            );
            if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
                $this->logAction($this->lng->txtlng("assessment", "log_question_position_changed", ilObjAssessmentFolder::_getLogLanguage()) . ": " . ($data->sequence) . " => " . ($data->sequence - 1), $question_id);
            }
        }
        $this->loadQuestions();
    }

    /**
    * Moves a question down in order
    *
    * @param integer $question_id The database id of the question to be moved down
    * @access public
    * @see $test_id
    */
    public function questionMoveDown($question_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        // Move a question down in sequence
        $result = $ilDB->queryF(
            "SELECT * FROM tst_test_question WHERE test_fi=%s AND question_fi=%s",
            array('integer','integer'),
            array($this->getTestId(), $question_id)
        );
        $data = $ilDB->fetchObject($result);
        $result = $ilDB->queryF(
            "SELECT * FROM tst_test_question WHERE test_fi=%s AND sequence=%s",
            array('integer','integer'),
            array($this->getTestId(), $data->sequence + 1)
        );
        if ($result->numRows() == 1) {
            // OK, it's not the last question, so move it down
            $data_next = $ilDB->fetchObject($result);
            // change next dataset
            $ilDB->manipulateF(
                "UPDATE tst_test_question SET sequence=%s WHERE test_question_id=%s",
                array('integer','integer'),
                array($data->sequence, $data_next->test_question_id)
            );
            // move actual dataset down
            $ilDB->manipulateF(
                "UPDATE tst_test_question SET sequence=%s WHERE test_question_id=%s",
                array('integer','integer'),
                array($data->sequence + 1, $data->test_question_id)
            );
            if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
                $this->logAction($this->lng->txtlng("assessment", "log_question_position_changed", ilObjAssessmentFolder::_getLogLanguage()) . ": " . ($data->sequence) . " => " . ($data->sequence + 1), $question_id);
            }
        }
        $this->loadQuestions();
    }

    /**
    * Takes a question and creates a copy of the question for use in the test
    *
    * @param integer $question_id The database id of the question
    * @result integer The database id of the copied question
    */
    public function duplicateQuestionForTest($question_id): int
    {
        $question = ilObjTest::_instanciateQuestion($question_id);
        $duplicate_id = $question->duplicate(true, '', '', '', $this->getId());
        return $duplicate_id;
    }

    /**
     * Insert a question in the list of questions
     *
     * @param ilTestQuestionSetConfig $testQuestionSetConfig
     * @param integer $question_id The database id of the inserted question
     * @param boolean $linkOnly
     * @return integer $duplicate_id
     */
    public function insertQuestion(ilTestQuestionSetConfig $testQuestionSetConfig, $question_id, $linkOnly = false): int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        if ($linkOnly) {
            $duplicate_id = $question_id;
        } else {
            $duplicate_id = $this->duplicateQuestionForTest($question_id);
        }

        // get maximum sequence index in test
        $result = $ilDB->queryF(
            "SELECT MAX(sequence) seq FROM tst_test_question WHERE test_fi=%s",
            array('integer'),
            array($this->getTestId())
        );
        $sequence = 1;

        if ($result->numRows() == 1) {
            $data = $ilDB->fetchObject($result);
            $sequence = $data->seq + 1;
        }

        $next_id = $ilDB->nextId('tst_test_question');
        $affectedRows = $ilDB->manipulateF(
            "INSERT INTO tst_test_question (test_question_id, test_fi, question_fi, sequence, tstamp) VALUES (%s, %s, %s, %s, %s)",
            array('integer', 'integer','integer','integer','integer'),
            array($next_id, $this->getTestId(), $duplicate_id, $sequence, time())
        );
        if ($affectedRows == 1) {
            if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
                $this->logAction($this->lng->txtlng("assessment", "log_question_added", ilObjAssessmentFolder::_getLogLanguage()) . ": " . $sequence, $duplicate_id);
            }
        }
        // remove test_active entries, because test has changed
        $affectedRows = $ilDB->manipulateF(
            "DELETE FROM tst_active WHERE test_fi = %s",
            array('integer'),
            array($this->getTestId())
        );
        $this->loadQuestions();
        $this->saveCompleteStatus($testQuestionSetConfig);
        return $duplicate_id;
    }

    /**
    * Returns the titles of the test questions in question sequence
    *
    * @return array The question titles
    * @access public
    * @see $questions
    */
    public function &getQuestionTitles(): array
    {
        $titles = array();
        if ($this->getQuestionSetType() == self::QUESTION_SET_TYPE_FIXED) {
            global $DIC;
            $ilDB = $DIC['ilDB'];
            $result = $ilDB->queryF(
                "SELECT qpl_questions.title FROM tst_test_question, qpl_questions WHERE tst_test_question.test_fi = %s AND tst_test_question.question_fi = qpl_questions.question_id ORDER BY tst_test_question.sequence",
                array('integer'),
                array($this->getTestId())
            );
            while ($row = $ilDB->fetchAssoc($result)) {
                array_push($titles, $row["title"]);
            }
        }
        return $titles;
    }

    /**
    * Returns the titles of the test questions in question sequence
    *
    * @return array The question titles
    * @access public
    * @see $questions
    */
    public function &getQuestionTitlesAndIndexes(): array
    {
        $titles = array();
        if ($this->getQuestionSetType() == self::QUESTION_SET_TYPE_FIXED) {
            global $DIC;
            $ilDB = $DIC['ilDB'];
            $result = $ilDB->queryF(
                "SELECT qpl_questions.title, qpl_questions.question_id FROM tst_test_question, qpl_questions WHERE tst_test_question.test_fi = %s AND tst_test_question.question_fi = qpl_questions.question_id ORDER BY tst_test_question.sequence",
                array('integer'),
                array($this->getTestId())
            );
            while ($row = $ilDB->fetchAssoc($result)) {
                $titles[$row['question_id']] = $row["title"];
            }
        }
        return $titles;
    }

    // fau: testNav - add number parameter (to show if title should not be shown)
    /**
     * Returns the title of a test question and checks if the title output is allowed.
     * If not, the localized text "question" will be returned.
     *
     * @param string $title The original title of the question
     * @param integer $nr The number of the question in the sequence
     * @return string The title for the question title output
     * @access public
     */
    public function getQuestionTitle($title, $nr = null): string
    {
        if ($this->getTitleOutput() !== 2) {
            return $title;
        }

        if ($this->getTitleOutput() === 2 && isset($nr)) {
            return $this->lng->txt("ass_question") . ' ' . $nr;
        }

        return $this->lng->txt("ass_question");
    }
    // fau.

    /**
    * Returns the dataset for a given question id
    *
    * @param integer $question_id The database id of the question
    * @return object Question dataset
    * @access public
    * @see $questions
    */
    public function getQuestionDataset($question_id): object
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT qpl_questions.*, qpl_qst_type.type_tag FROM qpl_questions, qpl_qst_type WHERE qpl_questions.question_id = %s AND qpl_questions.question_type_fi = qpl_qst_type.question_type_id",
            array('integer'),
            array($question_id)
        );
        $row = $ilDB->fetchObject($result);
        return $row;
    }

    /**
    * Get the id's of the questions which are already part of the test
    *
    * @return array An array containing the already existing questions
    * @access	public
    */
    public function &getExistingQuestions($pass = null): array
    {
        global $DIC;
        $ilUser = $DIC['ilUser'];
        $ilDB = $DIC['ilDB'];

        $existing_questions = array();
        $active_id = $this->getActiveIdOfUser($ilUser->getId());
        if ($this->isRandomTest()) {
            if (is_null($pass)) {
                $pass = 0;
            }
            $result = $ilDB->queryF(
                "SELECT qpl_questions.original_id FROM qpl_questions, tst_test_rnd_qst WHERE tst_test_rnd_qst.active_fi = %s AND tst_test_rnd_qst.question_fi = qpl_questions.question_id AND tst_test_rnd_qst.pass = %s",
                array('integer','integer'),
                array($active_id, $pass)
            );
        } else {
            $result = $ilDB->queryF(
                "SELECT qpl_questions.original_id FROM qpl_questions, tst_test_question WHERE tst_test_question.test_fi = %s AND tst_test_question.question_fi = qpl_questions.question_id",
                array('integer'),
                array($this->getTestId())
            );
        }
        while ($data = $ilDB->fetchObject($result)) {
            if ($data->original_id === null) {
                continue;
            }

            array_push($existing_questions, $data->original_id);
        }
        return $existing_questions;
    }

    /**
    * Returns the question type of a question with a given id
    *
    * @param integer $question_id The database id of the question
    * @result string The question type string
    * @access private
    */
    public function getQuestionType($question_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        if ($question_id < 1) {
            return -1;
        }
        $result = $ilDB->queryF(
            "SELECT type_tag FROM qpl_questions, qpl_qst_type WHERE qpl_questions.question_id = %s AND qpl_questions.question_type_fi = qpl_qst_type.question_type_id",
            array('integer'),
            array($question_id)
        );
        if ($result->numRows() == 1) {
            $data = $ilDB->fetchObject($result);
            return $data->type_tag;
        } else {
            return "";
        }
    }

    /**
    * Write the initial entry for the tests working time to the database
    *
    * @param integer $user_id The database id of the user working with the test
    * @access	public
    */
    public function startWorkingTime($active_id, $pass)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $next_id = $ilDB->nextId('tst_times');
        $affectedRows = $ilDB->manipulateF(
            "INSERT INTO tst_times (times_id, active_fi, started, finished, pass, tstamp) VALUES (%s, %s, %s, %s, %s, %s)",
            array('integer', 'integer', 'timestamp', 'timestamp', 'integer', 'integer'),
            array($next_id, $active_id, date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), $pass, time())
        );
        return $next_id;
    }

    /**
    * Update the working time of a test when a question is answered
    *
    * @param integer $times_id The database id of a working time entry
    * @access	public
    */
    public function updateWorkingTime($times_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $affectedRows = $ilDB->manipulateF(
            "UPDATE tst_times SET finished = %s, tstamp = %s WHERE times_id = %s",
            array('timestamp', 'integer', 'integer'),
            array(date('Y-m-d H:i:s'), time(), $times_id)
        );
    }

    /**
    * Gets the id's of all questions a user already worked through
    *
    * @return array The question id's of the questions already worked through
    * @access	public
    */
    public function &getWorkedQuestions($active_id, $pass = null): array
    {
        global $DIC;
        $ilUser = $DIC['ilUser'];
        $ilDB = $DIC['ilDB'];

        if (is_null($pass)) {
            $result = $ilDB->queryF(
                "SELECT question_fi FROM tst_solutions WHERE active_fi = %s AND pass = %s GROUP BY question_fi",
                array('integer','integer'),
                array($active_id, 0)
            );
        } else {
            $result = $ilDB->queryF(
                "SELECT question_fi FROM tst_solutions WHERE active_fi = %s AND pass = %s GROUP BY question_fi",
                array('integer','integer'),
                array($active_id, $pass)
            );
        }
        $result_array = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            array_push($result_array, $row["question_fi"]);
        }
        return $result_array;
    }

    /**
    * Returns true if an active user completed a test pass and did not start a new pass
    *
    * @param integer $active_id The active id of the user
    * @param integer $currentpass The current test pass of the user
    * @return boolean true if an active user completed a test pass and did not start a new pass, false otherwise
    * @access public
    */
    public function isTestFinishedToViewResults($active_id, $currentpass): bool
    {
        $num = ilObjTest::lookupPassResultsUpdateTimestamp($active_id, $currentpass);
        return ((($currentpass > 0) && ($num == 0)) || $this->isTestFinished($active_id)) ? true : false;
    }

    /**
    * Returns all questions of a test in test order
    *
    * @return array An array containing the id's as keys and the database row objects as values
    * @access public
    */
    public function &getAllQuestions($pass = null): array
    {
        global $DIC;
        $ilUser = $DIC['ilUser'];
        $ilDB = $DIC['ilDB'];

        $result_array = array();
        if ($this->isRandomTest()) {
            $active_id = $this->getActiveIdOfUser($ilUser->getId());
            $this->loadQuestions($active_id, $pass);
            if (count($this->questions) == 0) {
                return $result_array;
            }
            if (is_null($pass)) {
                $pass = self::_getPass($active_id);
            }
            $result = $ilDB->queryF(
                "SELECT qpl_questions.* FROM qpl_questions, tst_test_rnd_qst WHERE tst_test_rnd_qst.question_fi = qpl_questions.question_id AND tst_test_rnd_qst.active_fi = %s AND tst_test_rnd_qst.pass = %s AND " . $ilDB->in('qpl_questions.question_id', $this->questions, false, 'integer'),
                array('integer','integer'),
                array($active_id, $pass)
            );
        } else {
            if (count($this->questions) == 0) {
                return $result_array;
            }
            $result = $ilDB->query("SELECT qpl_questions.* FROM qpl_questions, tst_test_question WHERE tst_test_question.question_fi = qpl_questions.question_id AND " . $ilDB->in('qpl_questions.question_id', $this->questions, false, 'integer'));
        }
        while ($row = $ilDB->fetchAssoc($result)) {
            $result_array[$row["question_id"]] = $row;
        }
        return $result_array;
    }

    /**
    * Gets the active id of a given user
    *
    * @param integer $user_id The database id of the user
    * @param string $anonymous_id The anonymous id if the test is an anonymized test
    * @return integer The active ID
    * @access	public
    */
    public function getActiveIdOfUser($user_id = "", $anonymous_id = ""): ?int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $ilUser = $DIC['ilUser'];

        if (!$user_id) {
            $user_id = $ilUser->getId();
        }

        $tst_access_code = ilSession::get('tst_access_code');
        if (is_array($tst_access_code) &&
            $ilUser->getId() === ANONYMOUS_USER_ID &&
            isset($tst_access_code[$this->getTestId()]) &&
            $tst_access_code[$this->getTestId()] !== '') {
            $result = $ilDB->queryF(
                'SELECT active_id FROM tst_active WHERE user_fi = %s AND test_fi = %s AND anonymous_id = %s',
                ['integer', 'integer', 'text'],
                [$user_id, $this->test_id, $tst_access_code[$this->getTestId()]]
            );
        } elseif ((string) $anonymous_id !== '') {
            $result = $ilDB->queryF(
                'SELECT active_id FROM tst_active WHERE user_fi = %s AND test_fi = %s AND anonymous_id = %s',
                ['integer', 'integer', 'text'],
                [$user_id, $this->test_id, $anonymous_id]
            );
        } else {
            if ($ilUser->getId() === ANONYMOUS_USER_ID) {
                return null;
            }
            $result = $ilDB->queryF(
                'SELECT active_id FROM tst_active WHERE user_fi = %s AND test_fi = %s',
                ['integer', 'integer'],
                [$user_id, $this->test_id]
            );
        }

        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            return (int) $row['active_id'];
        }

        return 0;
    }

    public static function _getActiveIdOfUser($user_id = "", $test_id = "")
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $ilUser = $DIC['ilUser'];

        if (!$user_id) {
            $user_id = $ilUser->id;
        }
        if (!$test_id) {
            return "";
        }
        $result = $ilDB->queryF(
            "SELECT tst_active.active_id FROM tst_active WHERE user_fi = %s AND test_fi = %s",
            array('integer', 'integer'),
            array($user_id, $test_id)
        );
        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            return $row["active_id"];
        } else {
            return "";
        }
    }

    /**
    * Shuffles the values of a given array
    *
    * @param array $array An array which should be shuffled
    * @access public
    */
    public function pcArrayShuffle($array): array
    {
        $keys = array_keys($array);
        shuffle($keys);
        $result = array();
        foreach ($keys as $key) {
            $result[$key] = $array[$key];
        }
        return $result;
    }

    /**
     * Calculates the results of a test for a given user
     * and returns an array with all test results
     *
     * @return array An array containing the test results for the given user
     */
    public function &getTestResult(
        $active_id,
        $pass = null,
        bool $ordered_sequence = false,
        bool $considerHiddenQuestions = true,
        bool $considerOptionalQuestions = true
    ): array {
        global $DIC;
        $tree = $DIC['tree'];
        $ilDB = $DIC['ilDB'];
        $lng = $DIC['lng'];
        $component_repository = $DIC['component.repository'];

        $results = $this->getResultsForActiveId($active_id);

        if ($pass === null) {
            $pass = $results['pass'];
        }

        $testSessionFactory = new ilTestSessionFactory($this);
        $testSession = $testSessionFactory->getSession($active_id);

        $testSequenceFactory = new ilTestSequenceFactory($ilDB, $lng, $component_repository, $this);
        $testSequence = $testSequenceFactory->getSequenceByActiveIdAndPass($active_id, $pass);

        if ($this->isDynamicTest()) {
            $dynamicQuestionSetConfig = new ilObjTestDynamicQuestionSetConfig($tree, $ilDB, $component_repository, $this);
            $dynamicQuestionSetConfig->loadFromDb();

            $testSequence->loadFromDb($dynamicQuestionSetConfig);
            $testSequence->loadQuestions($dynamicQuestionSetConfig, new ilTestDynamicQuestionSetFilterSelection());

            $sequence = $testSequence->getUserSequenceQuestions();
        } else {
            $testSequence->setConsiderHiddenQuestionsEnabled($considerHiddenQuestions);
            $testSequence->setConsiderOptionalQuestionsEnabled($considerOptionalQuestions);

            $testSequence->loadFromDb();
            $testSequence->loadQuestions();

            if ($ordered_sequence) {
                $sequence = $testSequence->getOrderedSequenceQuestions();
            } else {
                $sequence = $testSequence->getUserSequenceQuestions();
            }
        }

        $arrResults = [];

        $query = "
			SELECT		tst_test_result.question_fi,
						tst_test_result.points reached,
						tst_test_result.hint_count requested_hints,
						tst_test_result.hint_points hint_points,
						tst_test_result.answered answered

			FROM		tst_test_result

			LEFT JOIN	tst_solutions
			ON			tst_solutions.active_fi = tst_test_result.active_fi
			AND			tst_solutions.question_fi = tst_test_result.question_fi

			WHERE		tst_test_result.active_fi = %s
			AND			tst_test_result.pass = %s
		";

        $solutionresult = $ilDB->queryF(
            $query,
            array('integer', 'integer'),
            array($active_id, $pass)
        );

        while ($row = $ilDB->fetchAssoc($solutionresult)) {
            $arrResults[ $row['question_fi'] ] = $row;
        }

        $numWorkedThrough = count($arrResults);

        $IN_question_ids = $ilDB->in('qpl_questions.question_id', $sequence, false, 'integer');

        $query = "
			SELECT		qpl_questions.*,
						qpl_qst_type.type_tag,
						qpl_sol_sug.question_fi has_sug_sol

			FROM		qpl_qst_type,
						qpl_questions

			LEFT JOIN	qpl_sol_sug
			ON			qpl_sol_sug.question_fi = qpl_questions.question_id

			WHERE		qpl_qst_type.question_type_id = qpl_questions.question_type_fi
			AND			$IN_question_ids
		";

        $result = $ilDB->query($query);

        $unordered = [];

        $key = 1;

        $obligationsAnswered = true;

        while ($row = $ilDB->fetchAssoc($result)) {
            if (!isset($arrResults[ $row['question_id'] ])) {
                $percentvalue = 0.0;
            } else {
                $percentvalue = (
                    $row['points'] ? $arrResults[$row['question_id']]['reached'] / $row['points'] : 0
                );
            }
            if ($percentvalue < 0) {
                $percentvalue = 0.0;
            }

            $data = [
                "nr" => "$key",
                "title" => ilLegacyFormElementsUtil::prepareFormOutput($row['title']),
                "max" => round($row['points'], 2),
                "reached" => round($arrResults[$row['question_id']]['reached'] ?? 0, 2),
                'requested_hints' => $arrResults[$row['question_id']]['requested_hints'] ?? 0,
                'hint_points' => $arrResults[$row['question_id']]['hint_points'] ?? 0,
                "percent" => sprintf("%2.2f ", ($percentvalue) * 100) . "%",
                "solution" => ($row['has_sug_sol']) ? assQuestion::_getSuggestedSolutionOutput($row['question_id']) : '',
                "type" => $row["type_tag"],
                "qid" => $row['question_id'],
                "original_id" => $row["original_id"],
                "workedthrough" => isset($arrResults[$row['question_id']]) ? 1 : 0,
                'answered' => $arrResults[$row['question_id']]['answered'] ?? 0
            ];

            if (!isset($arrResults[ $row['question_id'] ]['answered']) || !$arrResults[ $row['question_id'] ]['answered']) {
                $obligationsAnswered = false;
            }

            $unordered[ $row['question_id'] ] = $data;

            $key++;
        }

        $numQuestionsTotal = count($unordered);

        $pass_max = 0;
        $pass_reached = 0;
        $pass_requested_hints = 0;
        $pass_hint_points = 0;
        $key = 1;

        $found = [];

        foreach ($sequence as $qid) {
            // building pass point sums based on prepared data
            // for question that exists in users qst sequence
            $pass_max += round($unordered[$qid]['max'], 2);
            $pass_reached += round($unordered[$qid]['reached'], 2);
            $pass_requested_hints += $unordered[$qid]['requested_hints'];
            $pass_hint_points += $unordered[$qid]['hint_points'];

            // pickup prepared data for question
            // that exists in users qst sequence
            $unordered[$qid]['nr'] = $key;
            array_push($found, $unordered[$qid]);

            // increment key counter
            $key++;
        }

        $unordered = null;

        if ($this->getScoreCutting() == 1) {
            if ($results['reached_points'] < 0) {
                $results['reached_points'] = 0;
            }

            if ($pass_reached < 0) {
                $pass_reached = 0;
            }
        }

        $found['pass']['total_max_points'] = $pass_max;
        $found['pass']['total_reached_points'] = $pass_reached;
        $found['pass']['total_requested_hints'] = $pass_requested_hints;
        $found['pass']['total_hint_points'] = $pass_hint_points;
        $found['pass']['percent'] = ($pass_max > 0) ? $pass_reached / $pass_max : 0;
        $found['pass']['obligationsAnswered'] = $obligationsAnswered;
        $found['pass']['num_workedthrough'] = $numWorkedThrough;
        $found['pass']['num_questions_total'] = $numQuestionsTotal;

        $found["test"]["total_max_points"] = $results['max_points'];
        $found["test"]["total_reached_points"] = $results['reached_points'];
        $found["test"]["total_requested_hints"] = $results['hint_count'];
        $found["test"]["total_hint_points"] = $results['hint_points'];
        $found["test"]["result_pass"] = $results['pass'];
        $found['test']['result_tstamp'] = $results['tstamp'];
        $found['test']['obligations_answered'] = $results['obligations_answered'];

        if ((!$found['pass']['total_reached_points']) or (!$found['pass']['total_max_points'])) {
            $percentage = 0.0;
        } else {
            $percentage = ($found['pass']['total_reached_points'] / $found['pass']['total_max_points']) * 100.0;

            if ($percentage < 0) {
                $percentage = 0.0;
            }
        }

        $found["test"]["passed"] = $results['passed'];

        return $found;
    }

    /**
    * Returns the number of persons who started the test
    *
    * @return integer The number of persons who started the test
    * @access public
    */
    public function evalTotalPersons(): int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT COUNT(active_id) total FROM tst_active WHERE test_fi = %s",
            array('integer'),
            array($this->getTestId())
        );
        $row = $ilDB->fetchAssoc($result);
        return $row["total"];
    }

    /**
    * Returns the complete working time in seconds a user worked on the test
    *
    * @return integer The working time in seconds
    * @access public
    */
    public function getCompleteWorkingTime($user_id): int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT tst_times.* FROM tst_active, tst_times WHERE tst_active.test_fi = %s AND tst_active.active_id = tst_times.active_fi AND tst_active.user_fi = %s",
            array('integer','integer'),
            array($this->getTestId(), $user_id)
        );
        $time = 0;
        while ($row = $ilDB->fetchAssoc($result)) {
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row["started"], $matches);
            $epoch_1 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row["finished"], $matches);
            $epoch_2 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            $time += ($epoch_2 - $epoch_1);
        }
        return $time;
    }

    /**
    * Returns the complete working time in seconds for all test participants
    *
    * @return array An array containing the working time in seconds for all test participants
    * @access public
    */
    public function &getCompleteWorkingTimeOfParticipants(): array
    {
        return $this->_getCompleteWorkingTimeOfParticipants($this->getTestId());
    }

    /**
    * Returns the complete working time in seconds for all test participants
    *
    * @param integer $test_id The database ID of the test
    * @return array An array containing the working time in seconds for all test participants
    * @access public
    */
    public function &_getCompleteWorkingTimeOfParticipants($test_id): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT tst_times.* FROM tst_active, tst_times WHERE tst_active.test_fi = %s AND tst_active.active_id = tst_times.active_fi ORDER BY tst_times.active_fi, tst_times.started",
            array('integer'),
            array($test_id)
        );
        $time = 0;
        $times = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            if (!array_key_exists($row["active_fi"], $times)) {
                $times[$row["active_fi"]] = 0;
            }
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row["started"], $matches);
            $epoch_1 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row["finished"], $matches);
            $epoch_2 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            $times[$row["active_fi"]] += ($epoch_2 - $epoch_1);
        }
        return $times;
    }

    /**
    * Returns the complete working time in seconds for a test participant
    *
    * @return integer The working time in seconds for the test participant
    * @access public
    */
    public function getCompleteWorkingTimeOfParticipant($active_id): int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT tst_times.* FROM tst_active, tst_times WHERE tst_active.test_fi = %s AND tst_active.active_id = tst_times.active_fi AND tst_active.active_id = %s ORDER BY tst_times.active_fi, tst_times.started",
            array('integer','integer'),
            array($this->getTestId(), $active_id)
        );
        $time = 0;
        while ($row = $ilDB->fetchAssoc($result)) {
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row["started"], $matches);
            $epoch_1 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row["finished"], $matches);
            $epoch_2 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            $time += ($epoch_2 - $epoch_1);
        }
        return $time;
    }

    /**
    * Returns the complete working time in seconds for a test participant
    *
    * @return integer The working time in seconds for the test participant
    * @access public
    */
    public static function _getWorkingTimeOfParticipantForPass($active_id, $pass): int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT * FROM tst_times WHERE active_fi = %s AND pass = %s ORDER BY started",
            array('integer','integer'),
            array($active_id, $pass)
        );
        $time = 0;
        while ($row = $ilDB->fetchAssoc($result)) {
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row["started"], $matches);
            $epoch_1 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row["finished"], $matches);
            $epoch_2 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            $time += ($epoch_2 - $epoch_1);
        }
        return $time;
    }

    /**
    * Returns the first and last visit of a participant
    *
    * @param integer $active_id The active ID of the participant
    * @return array The first and last visit of a participant
    * @access public
    */
    public function getVisitTimeOfParticipant($active_id): array
    {
        return ilObjTest::_getVisitTimeOfParticipant($this->getTestId(), $active_id);
    }

    /**
    * Returns the first and last visit of a participant
    *
    * @param integer $test_id The database ID of the test
    * @param integer $active_id The active ID of the participant
    * @return array The first and last visit of a participant
    * @access public
    */
    public function _getVisitTimeOfParticipant($test_id, $active_id): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT tst_times.* FROM tst_active, tst_times WHERE tst_active.test_fi = %s AND tst_active.active_id = tst_times.active_fi AND tst_active.active_id = %s ORDER BY tst_times.started",
            array('integer','integer'),
            array($test_id, $active_id)
        );
        $firstvisit = 0;
        $lastvisit = 0;
        while ($row = $ilDB->fetchAssoc($result)) {
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row["started"], $matches);
            $epoch_1 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            if ($firstvisit == 0 || $epoch_1 < $firstvisit) {
                $firstvisit = $epoch_1;
            }
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row["finished"], $matches);
            $epoch_2 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            if ($epoch_2 > $lastvisit) {
                $lastvisit = $epoch_2;
            }
        }
        return array("firstvisit" => $firstvisit, "lastvisit" => $lastvisit);
    }

    /**
    * Returns the statistical evaluation of the test for a specified user
    */
    public function evalStatistical($active_id): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        //		$ilBench = $DIC['ilBench'];
        $pass = ilObjTest::_getResultPass($active_id);
        $test_result = &$this->getTestResult($active_id, $pass);
        $result = $ilDB->queryF(
            "SELECT tst_times.* FROM tst_active, tst_times WHERE tst_active.active_id = %s AND tst_active.active_id = tst_times.active_fi",
            array('integer'),
            array($active_id)
        );
        $times = array();
        $first_visit = 0;
        $last_visit = 0;
        while ($row = $ilDB->fetchObject($result)) {
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row->started, $matches);
            $epoch_1 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            if (!$first_visit) {
                $first_visit = $epoch_1;
            }
            if ($epoch_1 < $first_visit) {
                $first_visit = $epoch_1;
            }
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row->finished, $matches);
            $epoch_2 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            if (!$last_visit) {
                $last_visit = $epoch_2;
            }
            if ($epoch_2 > $last_visit) {
                $last_visit = $epoch_2;
            }
            $times[$row->active_fi] += ($epoch_2 - $epoch_1);
        }
        $max_time = 0;
        foreach ($times as $key => $value) {
            $max_time += $value;
        }
        if ((!$test_result["test"]["total_reached_points"]) or (!$test_result["test"]["total_max_points"])) {
            $percentage = 0.0;
        } else {
            $percentage = ($test_result["test"]["total_reached_points"] / $test_result["test"]["total_max_points"]) * 100.0;
            if ($percentage < 0) {
                $percentage = 0.0;
            }
        }
        $mark_obj = $this->mark_schema->getMatchingMark($percentage);
        $first_date = getdate($first_visit);
        $last_date = getdate($last_visit);
        $qworkedthrough = 0;
        foreach ($test_result as $key => $value) {
            if (preg_match("/\d+/", $key)) {
                $qworkedthrough += $value["workedthrough"];
            }
        }
        if (!$qworkedthrough) {
            $atimeofwork = 0;
        } else {
            $atimeofwork = $max_time / $qworkedthrough;
        }

        $obligationsAnswered = $test_result["test"]["obligations_answered"];

        $result_mark = "";
        $passed = "";

        if ($mark_obj) {
            $result_mark = $mark_obj->getShortName();

            if ($mark_obj->getPassed() && $obligationsAnswered) {
                $passed = 1;
            } else {
                $passed = 0;
            }
        }
        $percent_worked_through = 0;
        if (count($this->questions)) {
            $percent_worked_through = $qworkedthrough / count($this->questions);
        }
        $result_array = array(
            "qworkedthrough" => $qworkedthrough,
            "qmax" => count($this->questions),
            "pworkedthrough" => $percent_worked_through,
            "timeofwork" => $max_time,
            "atimeofwork" => $atimeofwork,
            "firstvisit" => $first_date,
            "lastvisit" => $last_date,
            "resultspoints" => $test_result["test"]["total_reached_points"],
            "maxpoints" => $test_result["test"]["total_max_points"],
            "resultsmarks" => $result_mark,
            "passed" => $passed,
            "distancemedian" => "0"
        );
        foreach ($test_result as $key => $value) {
            if (preg_match("/\d+/", $key)) {
                $result_array[$key] = $value;
            }
        }
        return $result_array;
    }

    /**
    * Returns an array with the total points of all users who passed the test
    * This array could be used for statistics
    *
    * @return array The total point values
    * @access public
    */
    public function &getTotalPointsPassedArray(): array
    {
        $totalpoints_array = array();
        $all_users = $this->evalTotalParticipantsArray();
        foreach ($all_users as $active_id => $user_name) {
            $test_result = &$this->getTestResult($active_id);
            $reached = $test_result["test"]["total_reached_points"];
            $total = $test_result["test"]["total_max_points"];
            $percentage = $total != 0 ? $reached / $total : 0;
            $mark = $this->mark_schema->getMatchingMark($percentage * 100.0);

            $obligationsAnswered = $test_result["test"]["obligations_answered"];

            if ($mark) {
                if ($mark->getPassed() && $obligationsAnswered) {
                    array_push($totalpoints_array, $test_result["test"]["total_reached_points"]);
                }
            }
        }
        return $totalpoints_array;
    }

    /**
     * Returns all persons who started the test
     *
     * @return array The active ids, names and logins of the persons who started the test
    */
    public function &getParticipants(): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $result = $ilDB->queryF(
            "SELECT tst_active.active_id, usr_data.usr_id, usr_data.firstname, usr_data.lastname, usr_data.title, usr_data.login FROM tst_active LEFT JOIN usr_data ON tst_active.user_fi = usr_data.usr_id WHERE tst_active.test_fi = %s ORDER BY usr_data.lastname ASC",
            array('integer'),
            array($this->getTestId())
        );
        $persons_array = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            $name = $this->lng->txt("anonymous");
            $fullname = $this->lng->txt("anonymous");
            $login = "";
            if (!$this->getAnonymity()) {
                if (strlen($row["firstname"] . $row["lastname"] . $row["title"]) == 0) {
                    $name = $this->lng->txt("deleted_user");
                    $fullname = $this->lng->txt("deleted_user");
                    $login = $this->lng->txt("unknown");
                } else {
                    $login = $row["login"];
                    if ($row["usr_id"] == ANONYMOUS_USER_ID) {
                        $name = $this->lng->txt("anonymous");
                        $fullname = $this->lng->txt("anonymous");
                    } else {
                        $name = trim($row["lastname"] . ", " . $row["firstname"] . " " . $row["title"]);
                        $fullname = trim($row["title"] . " " . $row["firstname"] . " " . $row["lastname"]);
                    }
                }
            }
            $persons_array[$row["active_id"]] = array(
                "name" => $name,
                "fullname" => $fullname,
                "login" => $login
            );
        }
        return $persons_array;
    }

    /**
    * Returns all persons who started the test
    *
    * @return array The user id's and names of the persons who started the test
    * @access public
    */
    public function evalTotalPersonsArray($name_sort_order = "asc"): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $result = $ilDB->queryF(
            "SELECT tst_active.user_fi, tst_active.active_id, usr_data.firstname, usr_data.lastname, usr_data.title FROM tst_active LEFT JOIN usr_data ON tst_active.user_fi = usr_data.usr_id WHERE tst_active.test_fi = %s ORDER BY usr_data.lastname " . strtoupper($name_sort_order),
            array('integer'),
            array($this->getTestId())
        );
        $persons_array = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            if ($this->getAccessFilteredParticipantList() && !$this->getAccessFilteredParticipantList()->isActiveIdInList($row["active_id"])) {
                continue;
            }

            if ($this->getAnonymity()) {
                $persons_array[$row["active_id"]] = $this->lng->txt("anonymous");
            } else {
                if (strlen($row["firstname"] . $row["lastname"] . $row["title"]) == 0) {
                    $persons_array[$row["active_id"]] = $this->lng->txt("deleted_user");
                } else {
                    if ($row["user_fi"] == ANONYMOUS_USER_ID) {
                        $persons_array[$row["active_id"]] = $row["lastname"];
                    } else {
                        $persons_array[$row["active_id"]] = trim($row["lastname"] . ", " . $row["firstname"] . " " . $row["title"]);
                    }
                }
            }
        }
        return $persons_array;
    }

    /**
    * Returns all participants who started the test
    *
    * @return array The active user id's and names of the persons who started the test
    */
    public function evalTotalParticipantsArray($name_sort_order = "asc"): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $result = $ilDB->queryF(
            "SELECT tst_active.user_fi, tst_active.active_id, usr_data.login, usr_data.firstname, usr_data.lastname, usr_data.title FROM tst_active LEFT JOIN usr_data ON tst_active.user_fi = usr_data.usr_id WHERE tst_active.test_fi = %s ORDER BY usr_data.lastname " . strtoupper($name_sort_order),
            array('integer'),
            array($this->getTestId())
        );
        $persons_array = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            if ($this->getAnonymity()) {
                $persons_array[$row["active_id"]] = array("name" => $this->lng->txt("anonymous"));
            } else {
                if (strlen($row["firstname"] . $row["lastname"] . $row["title"]) == 0) {
                    $persons_array[$row["active_id"]] = array("name" => $this->lng->txt("deleted_user"));
                } else {
                    if ($row["user_fi"] == ANONYMOUS_USER_ID) {
                        $persons_array[$row["active_id"]] = array("name" => $row["lastname"]);
                    } else {
                        $persons_array[$row["active_id"]] = array("name" => trim($row["lastname"] . ", " . $row["firstname"] . " " . $row["title"]), "login" => $row["login"]);
                    }
                }
            }
        }
        return $persons_array;
    }

    /**
    * Retrieves all the assigned questions for all test passes of a test participant
    *
    * @return array An associated array containing the questions
    * @access public
    */
    public function &getQuestionsOfTest($active_id): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        if ($this->isRandomTest()) {
            $ilDB->setLimit($this->getQuestionCount(), 0);
            $result = $ilDB->queryF(
                "SELECT tst_test_rnd_qst.sequence, tst_test_rnd_qst.question_fi, " .
                "tst_test_rnd_qst.pass, qpl_questions.points " .
                "FROM tst_test_rnd_qst, qpl_questions " .
                "WHERE tst_test_rnd_qst.question_fi = qpl_questions.question_id " .
                "AND tst_test_rnd_qst.active_fi = %s ORDER BY tst_test_rnd_qst.sequence",
                array('integer'),
                array($active_id)
            );
        } else {
            $result = $ilDB->queryF(
                "SELECT tst_test_question.sequence, tst_test_question.question_fi, " .
                "qpl_questions.points " .
                "FROM tst_test_question, tst_active, qpl_questions " .
                "WHERE tst_test_question.question_fi = qpl_questions.question_id " .
                "AND tst_active.active_id = %s AND tst_active.test_fi = tst_test_question.test_fi",
                array('integer'),
                array($active_id)
            );
        }
        $qtest = array();
        if ($result->numRows()) {
            while ($row = $ilDB->fetchAssoc($result)) {
                array_push($qtest, $row);
            }
        }
        return $qtest;
    }

    /**
    * Retrieves all the assigned questions for a test participant in a given test pass
    *
    * @return array An associated array containing the questions
    * @access public
    */
    public function &getQuestionsOfPass($active_id, $pass): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        if ($this->isRandomTest()) {
            $ilDB->setLimit($this->getQuestionCount(), 0);
            $result = $ilDB->queryF(
                "SELECT tst_test_rnd_qst.sequence, tst_test_rnd_qst.question_fi, " .
                "qpl_questions.points " .
                "FROM tst_test_rnd_qst, qpl_questions " .
                "WHERE tst_test_rnd_qst.question_fi = qpl_questions.question_id " .
                "AND tst_test_rnd_qst.active_fi = %s AND tst_test_rnd_qst.pass = %s " .
                "ORDER BY tst_test_rnd_qst.sequence",
                array('integer', 'integer'),
                array($active_id, $pass)
            );
        } else {
            $result = $ilDB->queryF(
                "SELECT tst_test_question.sequence, tst_test_question.question_fi, " .
                "qpl_questions.points " .
                "FROM tst_test_question, tst_active, qpl_questions " .
                "WHERE tst_test_question.question_fi = qpl_questions.question_id " .
                "AND tst_active.active_id = %s AND tst_active.test_fi = tst_test_question.test_fi",
                array('integer'),
                array($active_id)
            );
        }
        $qpass = array();
        if ($result->numRows()) {
            while ($row = $ilDB->fetchAssoc($result)) {
                array_push($qpass, $row);
            }
        }
        return $qpass;
    }

    /**
     * @var ilTestParticipantList
     */
    protected $accessFilteredParticipantList;


    public function getAccessFilteredParticipantList(): ?ilTestParticipantList
    {
        return $this->accessFilteredParticipantList;
    }

    /**
     * @param ilTestParticipantList $accessFilteredParticipantList
     */
    public function setAccessFilteredParticipantList($accessFilteredParticipantList): void
    {
        $this->accessFilteredParticipantList = $accessFilteredParticipantList;
    }

    /**
     * @return ilTestParticipantList
     */
    public function buildStatisticsAccessFilteredParticipantList(): ilTestParticipantList
    {
        $list = new ilTestParticipantList($this);
        $list->initializeFromDbRows($this->getTestParticipants());

        $list = $list->getAccessFilteredList(
            ilTestParticipantAccessFilter::getAccessStatisticsUserFilter($this->getRefId())
        );

        return $list;
    }

    public function getUnfilteredEvaluationData(): ilTestEvaluationData
    {
        /** @var $DIC ILIAS\DI\Container */
        global $DIC;

        $ilDB = $DIC->database();

        $data = new ilTestEvaluationData($this);

        $query = "
			SELECT		tst_test_result.*,
						qpl_questions.original_id,
						qpl_questions.title questiontitle,
						qpl_questions.points maxpoints

			FROM		tst_test_result, qpl_questions, tst_active

			WHERE		tst_active.active_id = tst_test_result.active_fi
			AND			qpl_questions.question_id = tst_test_result.question_fi
			AND			tst_active.test_fi = %s

			ORDER BY	tst_active.active_id ASC, tst_test_result.pass ASC, tst_test_result.tstamp DESC
		";

        $result = $ilDB->queryF(
            $query,
            array('integer'),
            array($this->getTestId())
        );

        $pass = null;
        $checked = array();
        $datasets = 0;
        $questionData = [];

        while ($row = $ilDB->fetchAssoc($result)) {
            if (!$data->participantExists($row["active_fi"])) {
                continue;
            }

            $participantObject = $data->getParticipant($row["active_fi"]);
            $passObject = $participantObject->getPass($row["pass"]);

            if (!($passObject instanceof ilTestEvaluationPassData)) {
                continue;
            }

            $passObject->addAnsweredQuestion(
                $row["question_fi"],
                $row["maxpoints"],
                $row["points"],
                $row['answered'],
                null,
                $row['manual']
            );
        }

        foreach (array_keys($data->getParticipants()) as $active_id) {
            if ($this->isRandomTest()) {
                for ($testpass = 0; $testpass <= $data->getParticipant($active_id)->getLastPass(); $testpass++) {
                    $ilDB->setLimit($this->getQuestionCount(), 0);

                    $query = "
						SELECT tst_test_rnd_qst.sequence, tst_test_rnd_qst.question_fi, qpl_questions.original_id,
						tst_test_rnd_qst.pass, qpl_questions.points, qpl_questions.title
						FROM tst_test_rnd_qst, qpl_questions
						WHERE tst_test_rnd_qst.question_fi = qpl_questions.question_id
						AND tst_test_rnd_qst.pass = %s
						AND tst_test_rnd_qst.active_fi = %s ORDER BY tst_test_rnd_qst.sequence
					";

                    $result = $ilDB->queryF(
                        $query,
                        array('integer','integer'),
                        array($testpass, $active_id)
                    );

                    if ($result->numRows()) {
                        while ($row = $ilDB->fetchAssoc($result)) {
                            $tpass = array_key_exists("pass", $row) ? $row["pass"] : 0;

                            $data->getParticipant($active_id)->addQuestion(
                                $row["original_id"],
                                $row["question_fi"],
                                $row["points"],
                                $row["sequence"],
                                $tpass
                            );

                            $data->addQuestionTitle($row["question_fi"], $row["title"]);
                        }
                    }
                }
            } elseif ($this->isDynamicTest()) {
                $lastPass = $data->getParticipant($active_id)->getLastPass();
                for ($testpass = 0; $testpass <= $lastPass; $testpass++) {
                    $dynamicQuestionSetConfig = new ilObjTestDynamicQuestionSetConfig(
                        $DIC->repositoryTree(),
                        $DIC->database(),
                        $DIC['component.repository'],
                        $this
                    );
                    $dynamicQuestionSetConfig->loadFromDb();

                    $testSequenceFactory = new ilTestSequenceFactory($DIC->database(), $DIC->language(), $DIC['component.repository'], $this);
                    $testSequence = $testSequenceFactory->getSequenceByActiveIdAndPass($active_id, $testpass);

                    $testSequence->loadFromDb($dynamicQuestionSetConfig);
                    $testSequence->loadQuestions($dynamicQuestionSetConfig, new ilTestDynamicQuestionSetFilterSelection());

                    $sequence = $testSequence->getUserSequenceQuestions();

                    $questionsIdsToRequest = array_diff(array_values($sequence), array_values($questionData));
                    if (count($questionsIdsToRequest) > 0) {
                        $questionIdsCondition = ' ' . $DIC->database()->in('question_id', array_values($questionsIdsToRequest), false, 'integer') . ' ';

                        $res = $DIC->database()->queryF(
                            "
							SELECT *
							FROM qpl_questions
							WHERE {$questionIdsCondition}",
                            array('integer'),
                            array($active_id)
                        );
                        while ($row = $DIC->database()->fetchAssoc($res)) {
                            $questionData[$row['question_id']] = $row;
                            $data->addQuestionTitle($row['question_id'], $row['title']);
                        }
                    }

                    foreach ($sequence as $questionId) {
                        if (!isset($questionData[$questionId])) {
                            continue;
                        }

                        $row = $questionData[$questionId];

                        $data->getParticipant(
                            $active_id
                        )->addQuestion(
                            $row['original_id'],
                            $row['question_id'],
                            $row['points'],
                            null,
                            $testpass
                        );
                    }
                }
            } else {
                $query = "
					SELECT tst_test_question.sequence, tst_test_question.question_fi,
					qpl_questions.points, qpl_questions.title, qpl_questions.original_id
					FROM tst_test_question, tst_active, qpl_questions
					WHERE tst_test_question.question_fi = qpl_questions.question_id
					AND tst_active.active_id = %s
					AND tst_active.test_fi = tst_test_question.test_fi
					ORDER BY tst_test_question.sequence
				";

                $result = $ilDB->queryF(
                    $query,
                    array('integer'),
                    array($active_id)
                );

                if ($result->numRows()) {
                    $questionsbysequence = array();

                    while ($row = $ilDB->fetchAssoc($result)) {
                        $questionsbysequence[$row["sequence"]] = $row;
                    }

                    $seqresult = $ilDB->queryF(
                        "SELECT * FROM tst_sequence WHERE active_fi = %s",
                        array('integer'),
                        array($active_id)
                    );

                    while ($seqrow = $ilDB->fetchAssoc($seqresult)) {
                        $questionsequence = unserialize($seqrow["sequence"]);

                        foreach ($questionsequence as $sidx => $seq) {
                            $data->getParticipant($active_id)->addQuestion(
                                $questionsbysequence[$seq]["original_id"],
                                $questionsbysequence[$seq]["question_fi"],
                                $questionsbysequence[$seq]["points"],
                                $sidx + 1,
                                $seqrow["pass"]
                            );

                            $data->addQuestionTitle(
                                $questionsbysequence[$seq]["question_fi"],
                                $questionsbysequence[$seq]["title"]
                            );
                        }
                    }
                }
            }
        }

        if ($this->getECTSOutput()) {
            $passed_array = &$this->getTotalPointsPassedArray();
        }

        foreach (array_keys($data->getParticipants()) as $active_id) {
            $tstUserData = $data->getParticipant($active_id);

            $percentage = $tstUserData->getReachedPointsInPercent();

            $obligationsAnswered = $tstUserData->areObligationsAnswered();

            $mark = $this->mark_schema->getMatchingMark($percentage);

            if (is_object($mark)) {
                $tstUserData->setMark($mark->getShortName());
                $tstUserData->setMarkOfficial($mark->getOfficialName());

                $tstUserData->setPassed(
                    $mark->getPassed() && $tstUserData->areObligationsAnswered()
                );
            }

            if ($this->getECTSOutput()) {
                $ects_mark = $this->getECTSGrade(
                    $passed_array,
                    $tstUserData->getReached(),
                    $tstUserData->getMaxPoints()
                );

                $tstUserData->setECTSMark($ects_mark);
            }

            $visitingTime = $this->getVisitTimeOfParticipant($active_id);

            $tstUserData->setFirstVisit($visitingTime["firstvisit"]);
            $tstUserData->setLastVisit($visitingTime["lastvisit"]);
        }

        return $data;
    }

    public static function _getQuestionCountAndPointsForPassOfParticipant($active_id, $pass): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $questionSetType = ilObjTest::lookupQuestionSetTypeByActiveId($active_id);

        switch ($questionSetType) {
            case ilObjTest::QUESTION_SET_TYPE_DYNAMIC:

                $res = $ilDB->queryF(
                    "
						SELECT		COUNT(qpl_questions.question_id) qcount,
									SUM(qpl_questions.points) qsum
						FROM		tst_active
						INNER JOIN	tst_tests
						ON			tst_tests.test_id = tst_active.test_fi
						INNER JOIN	tst_dyn_quest_set_cfg
						ON          tst_dyn_quest_set_cfg.test_fi = tst_tests.test_id
						INNER JOIN  qpl_questions
						ON          qpl_questions.obj_fi = tst_dyn_quest_set_cfg.source_qpl_fi
						AND         qpl_questions.original_id IS NULL
						AND         qpl_questions.complete = %s
						WHERE		tst_active.active_id = %s
					",
                    array('integer', 'integer'),
                    array(1, $active_id)
                );

                break;

            case ilObjTest::QUESTION_SET_TYPE_RANDOM:

                $res = $ilDB->queryF(
                    "
						SELECT		tst_test_rnd_qst.pass,
									COUNT(tst_test_rnd_qst.question_fi) qcount,
									SUM(qpl_questions.points) qsum

						FROM		tst_test_rnd_qst,
									qpl_questions

						WHERE		tst_test_rnd_qst.question_fi = qpl_questions.question_id
						AND			tst_test_rnd_qst.active_fi = %s
						AND			pass = %s

						GROUP BY	tst_test_rnd_qst.active_fi,
									tst_test_rnd_qst.pass
					",
                    array('integer', 'integer'),
                    array($active_id, $pass)
                );

                break;

            case ilObjTest::QUESTION_SET_TYPE_FIXED:

                $res = $ilDB->queryF(
                    "
						SELECT		COUNT(tst_test_question.question_fi) qcount,
									SUM(qpl_questions.points) qsum

						FROM		tst_test_question,
									qpl_questions,
									tst_active

						WHERE		tst_test_question.question_fi = qpl_questions.question_id
						AND			tst_test_question.test_fi = tst_active.test_fi
						AND			tst_active.active_id = %s

						GROUP BY	tst_test_question.test_fi
					",
                    array('integer'),
                    array($active_id)
                );

                break;

            default:

                throw new ilTestException("not supported question set type: $questionSetType");
        }

        $row = $ilDB->fetchAssoc($res);

        if (is_array($row)) {
            return array("count" => $row["qcount"], "points" => $row["qsum"]);
        }

        return array("count" => 0, "points" => 0);
    }

    public function &getCompleteEvaluationData($withStatistics = true, $filterby = "", $filtertext = ""): ilTestEvaluationData
    {
        $data = $this->getUnfilteredEvaluationData();
        if ($withStatistics) {
            $data->calculateStatistics();
        }
        $data->setFilter($filterby, $filtertext);
        return $data;
    }

    /**
    * Creates an associated array with the results of all participants of a test
    *
    * @return array An associated array containing the results
    * @access public
    */
    public function &evalResultsOverview(): array
    {
        return $this->_evalResultsOverview($this->getTestId());
    }

    /**
    * Creates an associated array with the results of all participants of a test
    *
    * @return array An associated array containing the results
    * @access public
    */
    public function &_evalResultsOverview($test_id): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT usr_data.usr_id, usr_data.firstname, usr_data.lastname, usr_data.title, usr_data.login, " .
            "tst_test_result.*, qpl_questions.original_id, qpl_questions.title questiontitle, " .
            "qpl_questions.points maxpoints " .
            "FROM tst_test_result, qpl_questions, tst_active " .
            "LEFT JOIN usr_data ON tst_active.user_fi = usr_data.usr_id " .
            "WHERE tst_active.active_id = tst_test_result.active_fi " .
            "AND qpl_questions.question_id = tst_test_result.question_fi " .
            "AND tst_active.test_fi = %s " .
            "ORDER BY tst_active.active_id, tst_test_result.pass, tst_test_result.tstamp",
            array('integer'),
            array($test_id)
        );
        $overview = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            if (!array_key_exists($row["active_fi"], $overview)) {
                $overview[$row["active_fi"]] = array();
                $overview[$row["active_fi"]]["firstname"] = $row["firstname"];
                $overview[$row["active_fi"]]["lastname"] = $row["lastname"];
                $overview[$row["active_fi"]]["title"] = $row["title"];
                $overview[$row["active_fi"]]["login"] = $row["login"];
                $overview[$row["active_fi"]]["usr_id"] = $row["usr_id"];
                $overview[$row["active_fi"]]["started"] = $row["started"];
                $overview[$row["active_fi"]]["finished"] = $row["finished"];
            }
            if (!array_key_exists($row["pass"], $overview[$row["active_fi"]])) {
                $overview[$row["active_fi"]][$row["pass"]] = array();
                $overview[$row["active_fi"]][$row["pass"]]["reached"] = 0;
                $overview[$row["active_fi"]][$row["pass"]]["maxpoints"] = $row["maxpoints"];
            }
            array_push($overview[$row["active_fi"]][$row["pass"]], $row);
            $overview[$row["active_fi"]][$row["pass"]]["reached"] += $row["points"];
        }
        return $overview;
    }

    /**
    * Creates an associated array with the results for a given participant of a test
    *
    * @param integer $active_id The active id of the participant
    * @return array An associated array containing the results
    * @access public
    */
    public function &evalResultsOverviewOfParticipant($active_id): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT usr_data.usr_id, usr_data.firstname, usr_data.lastname, usr_data.title, usr_data.login, " .
            "tst_test_result.*, qpl_questions.original_id, qpl_questions.title questiontitle, " .
            "qpl_questions.points maxpoints " .
            "FROM tst_test_result, qpl_questions, tst_active " .
            "LEFT JOIN usr_data ON tst_active.user_fi = usr_data.usr_id " .
            "WHERE tst_active.active_id = tst_test_result.active_fi " .
            "AND qpl_questions.question_id = tst_test_result.question_fi " .
            "AND tst_active.test_fi = %s AND tst_active.active_id = %s" .
            "ORDER BY tst_active.active_id, tst_test_result.pass, tst_test_result.tstamp",
            array('integer', 'integer'),
            array($this->getTestId(), $active_id)
        );
        $overview = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            if (!array_key_exists($row["active_fi"], $overview)) {
                $overview[$row["active_fi"]] = array();
                $overview[$row["active_fi"]]["firstname"] = $row["firstname"];
                $overview[$row["active_fi"]]["lastname"] = $row["lastname"];
                $overview[$row["active_fi"]]["title"] = $row["title"];
                $overview[$row["active_fi"]]["login"] = $row["login"];
                $overview[$row["active_fi"]]["usr_id"] = $row["usr_id"];
                $overview[$row["active_fi"]]["started"] = $row["started"];
                $overview[$row["active_fi"]]["finished"] = $row["finished"];
            }
            if (!array_key_exists($row["pass"], $overview[$row["active_fi"]])) {
                $overview[$row["active_fi"]][$row["pass"]] = array();
                $overview[$row["active_fi"]][$row["pass"]]["reached"] = 0;
                $overview[$row["active_fi"]][$row["pass"]]["maxpoints"] = $row["maxpoints"];
            }
            array_push($overview[$row["active_fi"]][$row["pass"]], $row);
            $overview[$row["active_fi"]][$row["pass"]]["reached"] += $row["points"];
        }
        return $overview;
    }

    /**
    * Builds a user name for the output depending on test type and existence of
    * the user
    *
    * @param int $user_id The database ID of the user
    * @param string $firstname The first name of the user
    * @param string $lastname The last name of the user
    * @param string $title The title of the user
    * @return string The output name of the user
    * @access public
    */
    public function buildName($user_id, $firstname, $lastname, $title): string
    {
        $name = "";
        if (strlen($firstname . $lastname . $title) == 0) {
            $name = $this->lng->txt("deleted_user");
        } else {
            if ($user_id == ANONYMOUS_USER_ID) {
                $name = $lastname;
            } else {
                $name = trim($lastname . ", " . $firstname . " " . $title);
            }
            if ($this->getAnonymity()) {
                $name = $this->lng->txt("anonymous");
            }
        }
        return $name;
    }

    /**
    * Builds a user name for the output depending on test type and existence of
    * the user
    *
    * @param boolean $is_anonymous Indicates if it is an anonymized test or not
    * @param int $user_id The database ID of the user
    * @param string $firstname The first name of the user
    * @param string $lastname The last name of the user
    * @param string $title The title of the user
    * @return string The output name of the user
    * @access public
    */
    public function _buildName($is_anonymous, $user_id, $firstname, $lastname, $title): string
    {
        global $DIC;
        $lng = $DIC['lng'];
        $name = "";
        if (strlen($firstname . $lastname . $title) == 0) {
            $name = $lng->txt("deleted_user");
        } else {
            if ($user_id == ANONYMOUS_USER_ID) {
                $name = $lastname;
            } else {
                $name = trim($lastname . ", " . $firstname . " " . $title);
            }
            if ($is_anonymous) {
                $name = $lng->txt("anonymous");
            }
        }
        return $name;
    }

    /**
    * Returns the average processing time for all started tests
    *
    * @return integer The average processing time for all started tests
    * @access public
    */
    public function evalTotalStartedAverageTime($activeIdsFilter = null): int
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */

        $query = "SELECT tst_times.* FROM tst_active, tst_times WHERE tst_active.test_fi = %s AND tst_active.active_id = tst_times.active_fi";

        if (is_array($activeIdsFilter) && count($activeIdsFilter)) {
            $query .= " AND " . $DIC->database()->in('active_id', $activeIdsFilter, false, 'integer');
        }

        $result = $DIC->database()->queryF($query, array('integer'), array($this->getTestId()));
        $times = array();
        while ($row = $DIC->database()->fetchObject($result)) {
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row->started, $matches);
            $epoch_1 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row->finished, $matches);
            $epoch_2 = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            if (isset($times[$row->active_fi])) {
                $times[$row->active_fi] += ($epoch_2 - $epoch_1);
            } else {
                $times[$row->active_fi] = ($epoch_2 - $epoch_1);
            }
        }
        $max_time = 0;
        $counter = 0;
        foreach ($times as $key => $value) {
            $max_time += $value;
            $counter++;
        }
        if ($counter) {
            $average_time = round($max_time / $counter);
        } else {
            $average_time = 0;
        }
        return $average_time;
    }

    /**
    * Returns the available question pools for the active user
    *
    * @return array The available question pools
    * @access public
    */
    public function getAvailableQuestionpools($use_object_id = false, $equal_points = false, $could_be_offline = false, $show_path = false, $with_questioncount = false, $permission = "read"): array
    {
        return ilObjQuestionPool::_getAvailableQuestionpools($use_object_id, $equal_points, $could_be_offline, $show_path, $with_questioncount, $permission);
    }

    /**
    * Returns the estimated working time for the test calculated from the working time of the contained questions
    *
    * @return array An associative array containing the working time. array["h"] = hours, array["m"] = minutes, array["s"] = seconds
    * @access public
    */
    public function getEstimatedWorkingTime(): array
    {
        $time_in_seconds = 0;
        foreach ($this->questions as $question_id) {
            $question = ilObjTest::_instanciateQuestion($question_id);
            $est_time = $question->getEstimatedWorkingTime();
            $time_in_seconds += $est_time["h"] * 3600 + $est_time["m"] * 60 + $est_time["s"];
        }
        $hours = (int) ($time_in_seconds / 3600)	;
        $time_in_seconds = $time_in_seconds - ($hours * 3600);
        $minutes = (int) ($time_in_seconds / 60);
        $time_in_seconds = $time_in_seconds - ($minutes * 60);
        $result = array("hh" => $hours, "mm" => $minutes, "ss" => $time_in_seconds);
        return $result;
    }

    /**
    * Returns the image path for web accessable images of a test
    * The image path is under the CLIENT_WEB_DIR in assessment/REFERENCE_ID_OF_TEST/images
    *
    * @access public
    */
    public function getImagePath(): string
    {
        return CLIENT_WEB_DIR . "/assessment/" . $this->getId() . "/images/";
    }

    /**
    * Returns the web image path for web accessable images of a test
    * The image path is under the web accessable data dir in assessment/REFERENCE_ID_OF_TEST/images
    *
    * @access public
    */
    public function getImagePathWeb()
    {
        $webdir = ilFileUtils::removeTrailingPathSeparators(CLIENT_WEB_DIR) . "/assessment/" . $this->getId() . "/images/";
        return str_replace(
            ilFileUtils::removeTrailingPathSeparators(ILIAS_ABSOLUTE_PATH),
            ilFileUtils::removeTrailingPathSeparators(ILIAS_HTTP_PATH),
            $webdir
        );
    }

    /**
    * Creates a question GUI instance of a given question type
    *
    * @param integer $question_type The question type of the question
    * @param integer $question_id The question id of the question, if available
    * @return assQuestionGUI $questionGUI The question GUI instance
    * @access	public
    */
    public function createQuestionGUI($question_type, $question_id = -1): ?assQuestionGUI
    {
        if ((!$question_type) and ($question_id > 0)) {
            $question_type = $this->getQuestionType($question_id);
        }

        if (!strlen($question_type)) {
            return null;
        }

        assQuestion::_includeClass($question_type, 1);

        $question_type_gui = $question_type . 'GUI';
        $question = new $question_type_gui();

        if ($question_id > 0) {
            $question->object->loadFromDb($question_id);

            global $DIC;
            $ilCtrl = $DIC['ilCtrl'];
            $ilDB = $DIC['ilDB'];
            $ilUser = $DIC['ilUser'];
            $lng = $DIC['lng'];

            $feedbackObjectClassname = assQuestion::getFeedbackClassNameByQuestionType($question_type);
            $question->object->feedbackOBJ = new $feedbackObjectClassname($question->object, $ilCtrl, $ilDB, $lng);

            $assSettings = new ilSetting('assessment');
            $processLockerFactory = new ilAssQuestionProcessLockerFactory($assSettings, $ilDB);
            $processLockerFactory->setQuestionId($question->object->getId());
            $processLockerFactory->setUserId($ilUser->getId());
            $processLockerFactory->setAssessmentLogEnabled(ilObjAssessmentFolder::_enabledAssessmentLogging());
            $question->object->setProcessLocker($processLockerFactory->getLocker());
        }

        return $question;
    }

    /**
     * Creates an instance of a question with a given question id
     * @param int $question_id The question id
     * @throws InvalidArgumentException
     * @deprecated use assQuestion::_instanciateQuestion($question_id) instead
     */
    public static function _instanciateQuestion($question_id): ?assQuestion
    {
        if (strcmp((string) $question_id, "") !== 0) {
            return assQuestion::instantiateQuestion($question_id);
        }

        return null;
    }

    /**
    * Move questions to another position
    *
    * @param array $move_questions An array with the question id's of the questions to move
    * @param integer $target_index The question id of the target position
    * @param integer $insert_mode 0, if insert before the target position, 1 if insert after the target position
    * @access public
    */
    public function moveQuestions($move_questions, $target_index, $insert_mode)
    {
        $this->questions = array_values($this->questions);
        $array_pos = array_search($target_index, $this->questions);
        if ($insert_mode == 0) {
            $part1 = array_slice($this->questions, 0, $array_pos);
            $part2 = array_slice($this->questions, $array_pos);
        } elseif ($insert_mode == 1) {
            $part1 = array_slice($this->questions, 0, $array_pos + 1);
            $part2 = array_slice($this->questions, $array_pos + 1);
        }
        foreach ($move_questions as $question_id) {
            if (!(array_search($question_id, $part1) === false)) {
                unset($part1[array_search($question_id, $part1)]);
            }
            if (!(array_search($question_id, $part2) === false)) {
                unset($part2[array_search($question_id, $part2)]);
            }
        }
        $part1 = array_values($part1);
        $part2 = array_values($part2);
        $new_array = array_values(array_merge($part1, $move_questions, $part2));
        $this->questions = array();
        $counter = 1;
        foreach ($new_array as $question_id) {
            $this->questions[$counter] = $question_id;
            $counter++;
        }
        $this->saveQuestionsToDb();
    }


    /**
    * Returns true if the starting time of a test is reached
    * A starting time is not available for self assessment tests
    *
    * @return boolean true if the starting time is reached, otherwise false
    * @access public
    */
    public function startingTimeReached(): bool
    {
        if ($this->isStartingTimeEnabled() && $this->getStartingTime() != 0) {
            $now = time();
            if ($now < $this->getStartingTime()) {
                return false;
            }
        }
        return true;
    }

    /**
    * Returns true if the ending time of a test is reached
    * An ending time is not available for self assessment tests
    *
    * @return boolean true if the ending time is reached, otherwise false
    * @access public
    */
    public function endingTimeReached(): bool
    {
        if ($this->isEndingTimeEnabled() && $this->getEndingTime() != 0) {
            $now = time();
            if ($now > $this->getEndingTime()) {
                return true;
            }
        }
        return false;
    }

    /**
    * Calculates the available questions for a test
    *
    * @access public
    */
    public function getAvailableQuestions($arrFilter, $completeonly = 0): array
    {
        global $DIC;
        $component_repository = $DIC['component.repository'];
        $component_factory = $DIC['component.factory'];
        $lng = $DIC['lng'];
        $ilUser = $DIC['ilUser'];
        $ilDB = $DIC['ilDB'];

        $available_pools = array_keys(ilObjQuestionPool::_getAvailableQuestionpools($use_object_id = true, $equal_points = false, $could_be_offline = false, $showPath = false, $with_questioncount = false));
        $available = "";
        if (count($available_pools)) {
            $available = " AND " . $ilDB->in('qpl_questions.obj_fi', $available_pools, false, 'integer');
        } else {
            return array();
        }
        if ($completeonly) {
            $available .= " AND qpl_questions.complete = " . $ilDB->quote("1", 'text');
        }

        $where = "";
        if (is_array($arrFilter)) {
            if (array_key_exists('title', $arrFilter) && strlen($arrFilter['title'])) {
                $where .= " AND " . $ilDB->like('qpl_questions.title', 'text', "%%" . $arrFilter['title'] . "%%");
            }
            if (array_key_exists('description', $arrFilter) && strlen($arrFilter['description'])) {
                $where .= " AND " . $ilDB->like('qpl_questions.description', 'text', "%%" . $arrFilter['description'] . "%%");
            }
            if (array_key_exists('author', $arrFilter) && strlen($arrFilter['author'])) {
                $where .= " AND " . $ilDB->like('qpl_questions.author', 'text', "%%" . $arrFilter['author'] . "%%");
            }
            if (array_key_exists('type', $arrFilter) && strlen($arrFilter['type'])) {
                $where .= " AND qpl_qst_type.type_tag = " . $ilDB->quote($arrFilter['type'], 'text');
            }
            if (array_key_exists('qpl', $arrFilter) && strlen($arrFilter['qpl'])) {
                $where .= " AND " . $ilDB->like('object_data.title', 'text', "%%" . $arrFilter['qpl'] . "%%");
            }
        }

        $original_ids = &$this->getExistingQuestions();
        $original_clause = " qpl_questions.original_id IS NULL";
        if (count($original_ids)) {
            $original_clause = " qpl_questions.original_id IS NULL AND " . $ilDB->in('qpl_questions.question_id', $original_ids, true, 'integer');
        }

        $query_result = $ilDB->query("
			SELECT		qpl_questions.*, qpl_questions.tstamp,
						qpl_qst_type.type_tag, qpl_qst_type.plugin, qpl_qst_type.plugin_name,
						object_data.title parent_title
			FROM		qpl_questions, qpl_qst_type, object_data
			WHERE $original_clause $available
			AND object_data.obj_id = qpl_questions.obj_fi
			AND qpl_questions.tstamp > 0
			AND qpl_questions.question_type_fi = qpl_qst_type.question_type_id
			$where
		");
        $rows = array();

        if ($query_result->numRows()) {
            while ($row = $ilDB->fetchAssoc($query_result)) {
                $row = ilAssQuestionType::completeMissingPluginName($row);

                if (!$row['plugin']) {
                    $row[ 'ttype' ] = $lng->txt($row[ "type_tag" ]);

                    $rows[] = $row;
                    continue;
                }

                $plugin = $component_repository->getPluginByName($row['plugin_name']);
                if (!$plugin->isActive()) {
                    continue;
                }

                $pl = $component_factory->getPlugin($plugin->getId());
                $row[ 'ttype' ] = $pl->getQuestionTypeTranslation();

                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Receives parameters from a QTI parser and creates a valid ILIAS test object
     * @param ilQTIAssessment $assessment
     */
    public function fromXML(ilQTIAssessment $assessment)
    {
        ilSession::clear('import_mob_xhtml');

        $this->setDescription($assessment->getComment());
        $this->setTitle($assessment->getTitle());

        $this->setIntroductionEnabled(false);
        foreach ($assessment->objectives as $objectives) {
            foreach ($objectives->materials as $material) {
                $intro = $this->QTIMaterialToString($material);
                $this->setIntroduction($intro);
                $this->setIntroductionEnabled(strlen($intro) > 0);
            }
        }

        if (
            $assessment->getPresentationMaterial() &&
            $assessment->getPresentationMaterial()->getFlowMat(0) &&
            $assessment->getPresentationMaterial()->getFlowMat(0)->getMaterial(0)
        ) {
            $this->setFinalStatement($this->QTIMaterialToString($assessment->getPresentationMaterial()->getFlowMat(0)->getMaterial(0)));
        }

        foreach ($assessment->assessmentcontrol as $assessmentcontrol) {
            switch ($assessmentcontrol->getSolutionswitch()) {
                case "Yes":
                    $this->setInstantFeedbackSolution(1);
                    break;
                default:
                    $this->setInstantFeedbackSolution(0);
                    break;
            }
        }

        $this->setStartingTimeEnabled(false);
        $this->setEndingTimeEnabled(false);
        $this->setPasswordEnabled(false);
        $this->setLimitUsersEnabled(false);

        $this->saveToDb();
        $score_settings = $this->getScoreSettings();
        $scoring_settings = $score_settings->getScoringSettings();
        $gamification_settings = $score_settings->getGamificationSettings();
        $result_summary_settings = $score_settings->getResultSummarySettings();
        $result_details_settings = $score_settings->getResultDetailsSettings();
        foreach ($assessment->qtimetadata as $metadata) {
            switch ($metadata["label"]) {
                case "test_type":
                    // for old tests with a test type
                    $type = $metadata["entry"];
                    switch ($type) {
                        case 1:
                            // assessment
                            $this->setAnonymity(1);
                            break;
                        case 2:
                            // self assessment
                            break;
                        case 4:
                            // online exam
                            $this->setFixedParticipants(1);
                            $this->setListOfQuestionsSettings(7);
                            $this->setShowSolutionPrintview(1);
                            break;
                        case 5:
                            // varying random test
                            break;
                    }
                    break;
                case "sequence_settings":
                    $this->setSequenceSettings($metadata["entry"]);
                    break;
                case "solution_details":
                    $result_details_settings = $result_details_settings->withShowSolutionDetails((bool) $metadata["entry"]);
                    break;
                case "print_bs_with_res":
                    $result_details_settings = $result_details_settings->withPrintBestSolutionWithResult((bool) $metadata["entry"]);
                    break;
                case "author":
                    $this->setAuthor($metadata["entry"]);
                    break;
                case "nr_of_tries":
                    $this->setNrOfTries($metadata["entry"]);
                    break;
                case 'block_after_passed':
                    $this->setBlockPassesAfterPassedEnabled((bool) $metadata['entry']);
                    break;
                case "pass_waiting":
                    $this->setPassWaiting($metadata["entry"]);
                    break;
                case "kiosk":
                    $this->setKiosk($metadata["entry"]);
                    break;
                case "showfinalstatement":
                    $this->setShowFinalStatement($metadata["entry"]);
                    break;
                case "showinfo":
                    $this->setShowInfo($metadata["entry"]);
                    break;
                case "forcejs":
                    $this->setForceJS($metadata["entry"]);
                    break;
                case "customstyle":
                    $this->setCustomStyle($metadata["entry"]);
                    break;

                case "highscore_enabled":
                    $gamification_settings = $gamification_settings->withHighscoreEnabled((bool) $metadata["entry"]);
                    break;

                case "highscore_anon":
                    $gamification_settings = $gamification_settings->withHighscoreAnon((bool) $metadata["entry"]);
                    break;

                case "highscore_achieved_ts":
                    $gamification_settings = $gamification_settings->withHighscoreAchievedTS((bool) $metadata["entry"]);
                    break;

                case "highscore_score":
                    $gamification_settings = $gamification_settings->withHighscoreScore((bool)$metadata["entry"]);
                    break;

                case "highscore_percentage":
                    $gamification_settings = $gamification_settings->withHighscorePercentage((bool) $metadata["entry"]);
                    break;

                case "highscore_hints":
                    $gamification_settings = $gamification_settings->withHighscoreHints((bool) $metadata["entry"]);
                    break;

                case "highscore_wtime":
                    $gamification_settings = $gamification_settings->withHighscoreWTime((bool) $metadata["entry"]);
                    break;

                case "highscore_own_table":
                    $gamification_settings = $gamification_settings->withHighscoreOwnTable((bool) $metadata["entry"]);
                    break;

                case "highscore_top_table":
                    $gamification_settings = $gamification_settings->withHighscoreTopTable((bool) $metadata["entry"]);
                    break;

                case "highscore_top_num":
                    $gamification_settings = $gamification_settings->withHighscoreTopNum((int) $metadata["entry"]);
                    break;

                case "hide_previous_results":
                    if ($metadata["entry"] == 0) {
                        $this->setUsePreviousAnswers(1);
                    } else {
                        $this->setUsePreviousAnswers(0);
                    }
                    break;
                case "use_previous_answers":
                    $this->setUsePreviousAnswers($metadata["entry"]);
                    break;
                case "answer_feedback":
                    $this->setAnswerFeedback($metadata["entry"]);
                    break;
                case "title_output":
                case "hide_title_points":
                    $this->setTitleOutput($metadata["entry"]);
                    break;
                case "question_set_type":
                    $this->setQuestionSetType($metadata["entry"]);
                    break;
                case "random_test":
                    if ($metadata["entry"]) {
                        $this->setQuestionSetType(self::QUESTION_SET_TYPE_RANDOM);
                    } else {
                        $this->setQuestionSetType(self::QUESTION_SET_TYPE_FIXED);
                    }
                    break;
                case "results_presentation":
                    $result_details_settings = $result_details_settings->withResultsPresentation((int) $metadata["entry"]);
                    break;
                case "reset_processing_time":
                    $this->setResetProcessingTime($metadata["entry"]);
                    break;
                case "instant_verification":
                    $this->setInstantFeedbackSolution($metadata["entry"]);
                    break;
                case "follow_qst_answer_fixation":
                    $this->setFollowupQuestionAnswerFixationEnabled((bool) $metadata["entry"]);
                    break;
                case "instant_feedback_answer_fixation":
                    $this->setInstantFeedbackAnswerFixationEnabled((bool) $metadata["entry"]);
                    break;
                case "force_instant_feedback":
                    $this->setForceInstantFeedbackEnabled((bool) $metadata["entry"]);
                    break;
                case "answer_feedback_points":
                    $this->setAnswerFeedbackPoints($metadata["entry"]);
                    break;
                case "anonymity":
                    $this->setAnonymity($metadata["entry"]);
                    break;
                case "show_cancel":
                    $this->setShowCancel($metadata["entry"]);
                    break;
                case "show_marker":
                    $this->setShowMarker($metadata["entry"]);
                    break;
                case "fixed_participants":
                    $this->setFixedParticipants($metadata["entry"]);
                    break;
                case "score_reporting":
                    $this->setScoreReporting((int) $metadata["entry"]);
                    break;
                case "shuffle_questions":
                    $this->setShuffleQuestions($metadata["entry"]);
                    break;
                case "count_system":
                    $scoring_settings = $scoring_settings->withCountSystem((int) $metadata["entry"]);
                    break;
                case "mailnotification":
                    $this->setMailNotification($metadata["entry"]);
                    break;
                case "mailnottype":
                    $this->setMailNotificationType($metadata["entry"]);
                    break;
                case "exportsettings":
                    $result_details_settings = $result_details_settings->withExportSettings((int) $metadata["entry"]);
                    break;
                case "score_cutting":
                    $scoring_settings = $scoring_settings->withScoreCutting((int)$metadata["entry"]);
                    break;
                case "password":
                    $this->setPassword($metadata["entry"]);
                    $this->setPasswordEnabled(strlen($metadata["entry"]) > 0);
                    break;
                case "allowedUsers":
                    $this->setAllowedUsers($metadata["entry"]);
                    $this->setLimitUsersEnabled((int) $metadata["entry"] > 0);
                    break;
                case "allowedUsersTimeGap":
                    $this->setAllowedUsersTimeGap($metadata["entry"]);
                    break;
                case "pass_scoring":
                    $scoring_settings = $scoring_settings->withPassScoring((int) $metadata["entry"]);
                    break;
                case 'pass_deletion_allowed':
                    $result_summary_settings = $result_summary_settings->withPassDeletionAllowed((bool) $metadata["entry"]);
                    break;
                case "show_summary":
                    $this->setListOfQuestionsSettings($metadata["entry"]);
                    break;
                case "reporting_date":
                    $iso8601period = $metadata["entry"];
                    if (preg_match("/P(\d+)Y(\d+)M(\d+)DT(\d+)H(\d+)M(\d+)S/", $iso8601period, $matches)) {
                        $this->setReportingDate(sprintf("%02d%02d%02d%02d%02d%02d", $matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6]));
                    }
                    break;
                case 'enable_processing_time':
                    $this->setEnableProcessingTime($metadata['entry']);
                    break;
                case "processing_time":
                    $this->setProcessingTime($metadata['entry']);
                    break;
                case "starting_time":
                    $iso8601period = $metadata["entry"];
                    if (preg_match("/P(\d+)Y(\d+)M(\d+)DT(\d+)H(\d+)M(\d+)S/", $iso8601period, $matches)) {
                        $date_time = new ilDateTime(sprintf("%02d-%02d-%02d %02d:%02d:%02d", $matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6]), IL_CAL_DATETIME);
                        $this->setStartingTime($date_time->get(IL_CAL_UNIX));
                        $this->setStartingTimeEnabled(true);
                    }
                    break;
                case "ending_time":
                    $iso8601period = $metadata["entry"];
                    if (preg_match("/P(\d+)Y(\d+)M(\d+)DT(\d+)H(\d+)M(\d+)S/", $iso8601period, $matches)) {
                        $date_time = new ilDateTime(sprintf("%02d-%02d-%02d %02d:%02d:%02d", $matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6]), IL_CAL_DATETIME);
                        $this->setEndingTime($date_time->get(IL_CAL_UNIX));
                        $this->setEndingTimeEnabled(true);
                    }
                    break;
                case "enable_examview":
                    $this->setEnableExamview($metadata["entry"]);
                    break;
                case 'show_examview_html':
                    $this->setShowExamviewHtml($metadata['entry']);
                    break;
                case 'show_examview_pdf':
                    $this->setShowExamviewPdf($metadata['entry']);
                    break;
                case 'redirection_mode':
                    $this->setRedirectionMode($metadata['entry']);
                    break;
                case 'redirection_url':
                    $this->setRedirectionUrl($metadata['entry']);
                    break;
                case 'examid_in_kiosk':
                case 'examid_in_test_pass':
                    $this->setShowExamIdInTestPassEnabled($metadata['entry']);
                    break;
                case 'show_exam_id':
                case 'examid_in_test_res':
                    $result_details_settings = $result_details_settings->withShowExamIdInTestResults((bool) $metadata["entry"]);
                    break;
                case 'enable_archiving':
                    $this->setEnableArchiving($metadata['entry']);
                    break;
                case 'sign_submission':
                    $this->setSignSubmission($metadata['entry']);
                    break;
                case 'char_selector_availability':
                    $this->setCharSelectorAvailability($metadata['entry']);
                    break;
                case 'char_selector_definition':
                    $this->setCharSelectorDefinition($metadata['entry']);
                    break;
                case 'skill_service':
                    $this->setSkillServiceEnabled((bool) $metadata['entry']);
                    break;
                case 'result_tax_filters':
                    $tax_ids = strlen($metadata['entry']) ? unserialize($metadata['entry']) : [];
                    $result_details_settings = $result_details_settings->withTaxonomyFilterIds($tax_ids);
                    break;
                case 'show_grading_status':
                    $result_summary_settings = $result_summary_settings->withShowGradingStatusEnabled((bool) $metadata["entry"]);
                    break;
                case 'show_grading_mark':
                    $result_summary_settings = $result_summary_settings->withShowGradingMarkEnabled((bool) $metadata["entry"]);
                    break;
                case 'activation_limited':
                    $this->setActivationLimited($metadata['entry']);
                    break;
                case 'activation_start_time':
                    $this->setActivationStartingTime($metadata['entry']);
                    break;
                case 'activation_end_time':
                    $this->setActivationEndingTime($metadata['entry']);
                    break;
                case 'activation_visibility':
                    $this->setActivationVisibility($metadata['entry']);
                    break;
                case 'autosave':
                    $this->setAutosave($metadata['entry']);
                    break;
                case 'autosave_ival':
                    $this->setAutosaveIval($metadata['entry']);
                    break;
                case 'offer_question_hints':
                    $this->setOfferingQuestionHintsEnabled($metadata['entry']);
                    break;
                case 'instant_feedback_specific':
                    $this->setSpecificAnswerFeedback($metadata['entry']);
                    break;
                case 'obligations_enabled':
                    $this->setObligationsEnabled($metadata['entry']);
                    break;
            }
            if (preg_match("/mark_step_\d+/", $metadata["label"])) {
                $xmlmark = $metadata["entry"];
                preg_match("/<short>(.*?)<\/short>/", $xmlmark, $matches);
                $mark_short = $matches[1];
                preg_match("/<official>(.*?)<\/official>/", $xmlmark, $matches);
                $mark_official = $matches[1];
                preg_match("/<percentage>(.*?)<\/percentage>/", $xmlmark, $matches);
                $mark_percentage = $matches[1];
                preg_match("/<passed>(.*?)<\/passed>/", $xmlmark, $matches);
                $mark_passed = $matches[1];
                $this->mark_schema->addMarkStep($mark_short, $mark_official, $mark_percentage, $mark_passed);
            }
        }

        $this->saveToDb();
        $score_settings = $score_settings
                ->withGamificationSettings($gamification_settings)
                ->withScoringSettings($scoring_settings)
                ->withResultDetailsSettings($result_details_settings)
                ->withResultSummarySettings($result_summary_settings);
        $this->getScoreSettingsRepository()->store($score_settings);
        $this->score_settings = $score_settings;
        $this->loadFromDb();

        // handle the import of media objects in XHTML code
        if (is_array(ilSession::get("import_mob_xhtml"))) {
            foreach (ilSession::get("import_mob_xhtml") as $mob) {
                $importfile = ilObjTest::_getImportDirectory() . '/' . ilSession::get('tst_import_subdir') . '/' . $mob["uri"];
                if (file_exists($importfile)) {
                    $media_object = ilObjMediaObject::_saveTempFileAsMediaObject(basename($importfile), $importfile, false);
                    ilObjMediaObject::_saveUsage($media_object->getId(), "tst:html", $this->getId());
                    $this->setIntroduction(ilRTE::_replaceMediaObjectImageSrc(str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $this->getIntroduction()), 1));
                    $this->setFinalStatement(ilRTE::_replaceMediaObjectImageSrc(str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $this->getFinalStatement()), 1));
                } else {
                    global $DIC;
                    $ilLog = $DIC['ilLog'];
                    $ilLog->write("Error: Could not open XHTML mob file for test introduction during test import. File $importfile does not exist!");
                }
            }
            $this->saveToDb();
        }
    }

    /**
     * Returns a QTI xml representation of the test
     *
     * @return string The QTI xml representation of the test
     */
    public function toXML(): string
    {
        $a_xml_writer = new ilXmlWriter();
        // set xml header
        $a_xml_writer->xmlHeader();
        $a_xml_writer->xmlSetDtdDef("<!DOCTYPE questestinterop SYSTEM \"ims_qtiasiv1p2p1.dtd\">");
        $a_xml_writer->xmlStartTag("questestinterop");

        $attrs = array(
            "ident" => "il_" . IL_INST_ID . "_tst_" . $this->getTestId(),
            "title" => $this->getTitle()
        );
        $a_xml_writer->xmlStartTag("assessment", $attrs);
        // add qti comment
        $a_xml_writer->xmlElement("qticomment", null, $this->getDescription());

        // add qti duration
        if ($this->enable_processing_time) {
            preg_match("/(\d+):(\d+):(\d+)/", $this->processing_time, $matches);
            $a_xml_writer->xmlElement("duration", null, sprintf("P0Y0M0DT%dH%dM%dS", $matches[1], $matches[2], $matches[3]));
        }

        // add the rest of the preferences in qtimetadata tags, because there is no correspondent definition in QTI
        $a_xml_writer->xmlStartTag("qtimetadata");
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "ILIAS_VERSION");
        $a_xml_writer->xmlElement("fieldentry", null, ILIAS_VERSION);
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // anonymity
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "anonymity");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getAnonymity()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // question set type (fixed, random, dynamic, ...)
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "question_set_type");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getQuestionSetType());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // sequence settings
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "sequence_settings");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getSequenceSettings());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // author
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "author");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getAuthor());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // reset processing time
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "reset_processing_time");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getResetProcessingTime());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // count system
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "count_system");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getCountSystem());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // multiple choice scoring
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "score_cutting");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getScoreCutting());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // multiple choice scoring
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "password");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getPassword());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // allowed users
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "allowedUsers");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getAllowedUsers());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // allowed users time gap
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "allowedUsersTimeGap");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getAllowedUsersTimeGap());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // pass scoring
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "pass_scoring");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getPassScoring());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        $a_xml_writer->xmlStartTag('qtimetadatafield');
        $a_xml_writer->xmlElement('fieldlabel', null, 'pass_deletion_allowed');
        $a_xml_writer->xmlElement('fieldentry', null, (int) $this->isPassDeletionAllowed());
        $a_xml_writer->xmlEndTag('qtimetadatafield');

        // score reporting date
        if ($this->getReportingDate()) {
            $a_xml_writer->xmlStartTag("qtimetadatafield");
            $a_xml_writer->xmlElement("fieldlabel", null, "reporting_date");
            preg_match("/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/", $this->reporting_date, $matches);
            $a_xml_writer->xmlElement("fieldentry", null, sprintf("P%dY%dM%dDT%dH%dM%dS", $matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6]));
            $a_xml_writer->xmlEndTag("qtimetadatafield");
        }
        // number of tries
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "nr_of_tries");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getNrOfTries()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // number of tries
        $a_xml_writer->xmlStartTag('qtimetadatafield');
        $a_xml_writer->xmlElement('fieldlabel', null, 'block_after_passed');
        $a_xml_writer->xmlElement('fieldentry', null, (int) $this->isBlockPassesAfterPassedEnabled());
        $a_xml_writer->xmlEndTag('qtimetadatafield');

        // pass_waiting
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "pass_waiting");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getPassWaiting());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // kiosk
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "kiosk");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getKiosk()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");


        //redirection_mode
        $a_xml_writer->xmlStartTag('qtimetadatafield');
        $a_xml_writer->xmlElement("fieldlabel", null, "redirection_mode");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getRedirectionMode());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        //redirection_url
        $a_xml_writer->xmlStartTag('qtimetadatafield');
        $a_xml_writer->xmlElement("fieldlabel", null, "redirection_url");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getRedirectionUrl());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // use previous answers
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "use_previous_answers");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getUsePreviousAnswers());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // hide title points
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "title_output");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getTitleOutput()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // results presentation
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "results_presentation");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getResultsPresentation()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // examid in test pass
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "examid_in_test_pass");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->isShowExamIdInTestPassEnabled()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // examid in kiosk
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "examid_in_test_res");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->isShowExamIdInTestResultsEnabled()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // solution details
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "show_summary");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getListOfQuestionsSettings()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // solution details
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "score_reporting");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getScoreReporting()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "solution_details");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->getShowSolutionDetails());
        $a_xml_writer->xmlEndTag("qtimetadatafield");
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "print_bs_with_res");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->getShowSolutionDetails() ? (int) $this->isBestSolutionPrintedWithResult() : 0);
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // solution details
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "instant_verification");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getInstantFeedbackSolution()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // answer specific feedback
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "answer_feedback");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getAnswerFeedback()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // answer specific feedback of reached points
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "answer_feedback_points");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getAnswerFeedbackPoints()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // followup question previous answer freezing
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "follow_qst_answer_fixation");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->isFollowupQuestionAnswerFixationEnabled());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // instant response answer freezing
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "instant_feedback_answer_fixation");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->isInstantFeedbackAnswerFixationEnabled());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // instant response forced
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "force_instant_feedback");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->isForceInstantFeedbackEnabled());
        $a_xml_writer->xmlEndTag("qtimetadatafield");


        // highscore
        $highscore_metadata = array(
            'highscore_enabled' => array('value' => $this->getHighscoreEnabled()),
            'highscore_anon' => array('value' => $this->getHighscoreAnon()),
            'highscore_achieved_ts' => array('value' => $this->getHighscoreAchievedTS()),
            'highscore_score' => array('value' => $this->getHighscoreScore()),
            'highscore_percentage' => array('value' => $this->getHighscorePercentage()),
            'highscore_hints' => array('value' => $this->getHighscoreHints()),
            'highscore_wtime' => array('value' => $this->getHighscoreWTime()),
            'highscore_own_table' => array('value' => $this->getHighscoreOwnTable()),
            'highscore_top_table' => array('value' => $this->getHighscoreTopTable()),
            'highscore_top_num' => array('value' => $this->getHighscoreTopNum()),
        );
        foreach ($highscore_metadata as $label => $data) {
            $a_xml_writer->xmlStartTag("qtimetadatafield");
            $a_xml_writer->xmlElement("fieldlabel", null, $label);
            $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $data['value']));
            $a_xml_writer->xmlEndTag("qtimetadatafield");
        }

        // show cancel
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "show_cancel");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getShowCancel()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // show marker
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "show_marker");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getShowMarker()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // fixed participants
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "fixed_participants");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getFixedParticipants()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // show final statement
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "showfinalstatement");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", (($this->getShowFinalStatement()) ? "1" : "0")));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // show introduction only
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "showinfo");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", (($this->getShowInfo()) ? "1" : "0")));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // mail notification
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "mailnotification");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getMailNotification());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // mail notification type
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "mailnottype");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getMailNotificationType());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // export settings
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "exportsettings");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getExportSettings());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // force JavaScript
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "forcejs");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", (($this->getForceJS()) ? "1" : "0")));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // custom style
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "customstyle");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getCustomStyle());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // shuffle questions
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "shuffle_questions");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getShuffleQuestions()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // processing time
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "processing_time");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getProcessingTime());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // enable_examview
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "enable_examview");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->getEnableExamview());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // show_examview_html
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "show_examview_html");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->getShowExamviewHtml());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // show_examview_pdf
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "show_examview_pdf");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->getShowExamviewPdf());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // enable_archiving
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "enable_archiving");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->getEnableArchiving());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // sign_submission
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "sign_submission");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->getSignSubmission());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // char_selector_availability
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "char_selector_availability");
        $a_xml_writer->xmlElement("fieldentry", null, sprintf("%d", $this->getCharSelectorAvailability()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // char_selector_definition
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "char_selector_definition");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getCharSelectorDefinition());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // skill_service
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "skill_service");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->isSkillServiceEnabled());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // result_tax_filters
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "result_tax_filters");
        $a_xml_writer->xmlElement("fieldentry", null, serialize($this->getResultFilterTaxIds()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // show_grading_status
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "show_grading_status");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->isShowGradingStatusEnabled());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // show_grading_mark
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "show_grading_mark");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->isShowGradingMarkEnabled());
        $a_xml_writer->xmlEndTag("qtimetadatafield");


        // starting time
        if ($this->getStartingTime()) {
            $a_xml_writer->xmlStartTag("qtimetadatafield");
            $a_xml_writer->xmlElement("fieldlabel", null, "starting_time");
            $backward_compatibility_format = $this->buildIso8601PeriodFromUnixtimeForExportCompatibility($this->starting_time);
            $a_xml_writer->xmlElement("fieldentry", null, $backward_compatibility_format);
            $a_xml_writer->xmlEndTag("qtimetadatafield");
        }
        // ending time
        if ($this->getEndingTime()) {
            $a_xml_writer->xmlStartTag("qtimetadatafield");
            $a_xml_writer->xmlElement("fieldlabel", null, "ending_time");
            $backward_compatibility_format = $this->buildIso8601PeriodFromUnixtimeForExportCompatibility($this->ending_time);
            $a_xml_writer->xmlElement("fieldentry", null, $backward_compatibility_format);
            $a_xml_writer->xmlEndTag("qtimetadatafield");
        }


        //activation_limited
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "activation_limited");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->isActivationLimited());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        //activation_start_time
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "activation_start_time");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->getActivationStartingTime());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        //activation_end_time
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "activation_end_time");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->getActivationEndingTime());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        //activation_visibility
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "activation_visibility");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->getActivationVisibility());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // autosave
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "autosave");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->getAutosave());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // autosave_ival
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "autosave_ival");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getAutosaveIval());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        //offer_question_hints
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "offer_question_hints");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->isOfferingQuestionHintsEnabled());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        //instant_feedback_specific
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "instant_feedback_specific");
        $a_xml_writer->xmlElement("fieldentry", null, $this->getSpecificAnswerFeedback());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        //instant_feedback_answer_fixation
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "instant_feedback_answer_fixation");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->isInstantFeedbackAnswerFixationEnabled());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        //obligations_enabled
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "obligations_enabled");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->areObligationsEnabled());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        //enable_processing_time
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", null, "enable_processing_time");
        $a_xml_writer->xmlElement("fieldentry", null, (int) $this->getEnableProcessingTime());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        foreach ($this->mark_schema->mark_steps as $index => $mark) {
            // mark steps
            $a_xml_writer->xmlStartTag("qtimetadatafield");
            $a_xml_writer->xmlElement("fieldlabel", null, "mark_step_$index");
            $a_xml_writer->xmlElement("fieldentry", null, sprintf(
                "<short>%s</short><official>%s</official><percentage>%.2f</percentage><passed>%d</passed>",
                $mark->getShortName(),
                $mark->getOfficialName(),
                $mark->getMinimumLevel(),
                $mark->getPassed()
            ));
            $a_xml_writer->xmlEndTag("qtimetadatafield");
        }
        $a_xml_writer->xmlEndTag("qtimetadata");

        // add qti objectives
        $a_xml_writer->xmlStartTag("objectives");
        $this->addQTIMaterial($a_xml_writer, $this->getIntroduction());
        $a_xml_writer->xmlEndTag("objectives");

        // add qti assessmentcontrol
        if ($this->getInstantFeedbackSolution() == 1) {
            $attrs = array(
                "solutionswitch" => "Yes"
            );
        } else {
            $attrs = null;
        }
        $a_xml_writer->xmlElement("assessmentcontrol", $attrs, null);

        if (strlen($this->getFinalStatement())) {
            // add qti presentation_material
            $a_xml_writer->xmlStartTag("presentation_material");
            $a_xml_writer->xmlStartTag("flow_mat");
            $this->addQTIMaterial($a_xml_writer, $this->getFinalStatement());
            $a_xml_writer->xmlEndTag("flow_mat");
            $a_xml_writer->xmlEndTag("presentation_material");
        }

        $attrs = array(
            "ident" => "1"
        );
        $a_xml_writer->xmlElement("section", $attrs, null);
        $a_xml_writer->xmlEndTag("assessment");
        $a_xml_writer->xmlEndTag("questestinterop");

        $xml = $a_xml_writer->xmlDumpMem(false);
        return $xml;
    }

    /**
     * @param $unix_timestamp
     * @return string
     */
    protected function buildIso8601PeriodFromUnixtimeForExportCompatibility($unix_timestamp): string
    {
        $date_time_unix = new ilDateTime($unix_timestamp, IL_CAL_UNIX);
        $date_time = $date_time_unix->get(IL_CAL_DATETIME);
        preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $date_time, $matches);
        $iso8601_period = sprintf("P%dY%dM%dDT%dH%dM%dS", $matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6]);
        return $iso8601_period;
    }

    /**
    * export pages of test to xml (see ilias_co.dtd)
    *
    * @param	object		$a_xml_writer	ilXmlWriter object that receives the
    *										xml data
    */
    public function exportPagesXML(&$a_xml_writer, $a_inst, $a_target_dir, &$expLog)
    {
        global $DIC;
        $ilBench = $DIC['ilBench'];

        $this->mob_ids = array();
        $this->file_ids = array();

        // MetaData
        $this->exportXMLMetaData($a_xml_writer);

        // PageObjects
        $expLog->write(date("[y-m-d H:i:s] ") . "Start Export Page Objects");
        $ilBench->start("ContentObjectExport", "exportPageObjects");
        $this->exportXMLPageObjects($a_xml_writer, $a_inst, $expLog);
        $ilBench->stop("ContentObjectExport", "exportPageObjects");
        $expLog->write(date("[y-m-d H:i:s] ") . "Finished Export Page Objects");

        // MediaObjects
        $expLog->write(date("[y-m-d H:i:s] ") . "Start Export Media Objects");
        $ilBench->start("ContentObjectExport", "exportMediaObjects");
        $this->exportXMLMediaObjects($a_xml_writer, $a_inst, $a_target_dir, $expLog);
        $ilBench->stop("ContentObjectExport", "exportMediaObjects");
        $expLog->write(date("[y-m-d H:i:s] ") . "Finished Export Media Objects");

        // FileItems
        $expLog->write(date("[y-m-d H:i:s] ") . "Start Export File Items");
        $ilBench->start("ContentObjectExport", "exportFileItems");
        $this->exportFileItems($a_target_dir, $expLog);
        $ilBench->stop("ContentObjectExport", "exportFileItems");
        $expLog->write(date("[y-m-d H:i:s] ") . "Finished Export File Items");
    }

    /**
    * export content objects meta data to xml (see ilias_co.dtd)
    *
    * @param	object		$a_xml_writer	ilXmlWriter object that receives the
    *										xml data
    */
    public function exportXMLMetaData(&$a_xml_writer)
    {
        $md2xml = new ilMD2XML($this->getId(), 0, $this->getType());
        $md2xml->setExportMode(true);
        $md2xml->startExport();
        $a_xml_writer->appendXML($md2xml->getXML());
    }

    /**
    * Returns the installation id for a given identifier
    *
    * @access	private
    */
    public function modifyExportIdentifier($a_tag, $a_param, $a_value)
    {
        if ($a_tag == "Identifier" && $a_param == "Entry") {
            $a_value = ilUtil::insertInstIntoID($a_value);
        }

        return $a_value;
    }


    /**
    * export page objects to xml (see ilias_co.dtd)
    *
    * @param	object		$a_xml_writer	ilXmlWriter object that receives the
    *										xml data
    */
    public function exportXMLPageObjects(&$a_xml_writer, $a_inst, &$expLog)
    {
        global $DIC;
        $ilBench = $DIC['ilBench'];

        foreach ($this->questions as $question_id) {
            $ilBench->start("ContentObjectExport", "exportPageObject");
            $expLog->write(date("[y-m-d H:i:s] ") . "Page Object " . $question_id);

            $attrs = array();
            $a_xml_writer->xmlStartTag("PageObject", $attrs);


            // export xml to writer object
            $ilBench->start("ContentObjectExport", "exportPageObject_XML");
            $page_object = new ilAssQuestionPage($question_id);
            $page_object->buildDom();
            $page_object->insertInstIntoIDs($a_inst);
            $mob_ids = $page_object->collectMediaObjects(false);
            $file_ids = ilPCFileList::collectFileItems($page_object, $page_object->getDomDoc());
            $xml = $page_object->getXMLFromDom(false, false, false, "", true);
            $xml = str_replace("&", "&amp;", $xml);
            $a_xml_writer->appendXML($xml);
            $page_object->freeDom();
            unset($page_object);

            $ilBench->stop("ContentObjectExport", "exportPageObject_XML");

            // collect media objects
            $ilBench->start("ContentObjectExport", "exportPageObject_CollectMedia");
            //$mob_ids = $page_obj->getMediaObjectIDs();
            foreach ($mob_ids as $mob_id) {
                $this->mob_ids[$mob_id] = $mob_id;
            }
            $ilBench->stop("ContentObjectExport", "exportPageObject_CollectMedia");

            // collect all file items
            $ilBench->start("ContentObjectExport", "exportPageObject_CollectFileItems");
            //$file_ids = $page_obj->getFileItemIds();
            foreach ($file_ids as $file_id) {
                $this->file_ids[$file_id] = $file_id;
            }
            $ilBench->stop("ContentObjectExport", "exportPageObject_CollectFileItems");

            $a_xml_writer->xmlEndTag("PageObject");
            //unset($page_obj);

            $ilBench->stop("ContentObjectExport", "exportPageObject");
        }
    }

    /**
    * export media objects to xml (see ilias_co.dtd)
    */
    public function exportXMLMediaObjects(&$a_xml_writer, $a_inst, $a_target_dir, &$expLog)
    {
        foreach ($this->mob_ids as $mob_id) {
            $expLog->write(date("[y-m-d H:i:s] ") . "Media Object " . $mob_id);
            if (ilObjMediaObject::_exists($mob_id)) {
                $media_obj = new ilObjMediaObject($mob_id);
                $media_obj->exportXML($a_xml_writer, $a_inst);
                $media_obj->exportFiles($a_target_dir);
                unset($media_obj);
            }
        }
    }

    /**
    * export files of file itmes
    *
    */
    public function exportFileItems($target_dir, &$expLog)
    {
        foreach ($this->file_ids as $file_id) {
            $expLog->write(date("[y-m-d H:i:s] ") . "File Item " . $file_id);
            $file_dir = $target_dir . '/objects/il_' . IL_INST_ID . '_file_' . $file_id;
            ilFileUtils::makeDir($file_dir);
            $file_obj = new ilObjFile($file_id, false);
            $source_file = $file_obj->getFile($file_obj->getVersion());
            if (!is_file($source_file)) {
                $source_file = $file_obj->getFile();
            }
            if (is_file($source_file)) {
                copy($source_file, $file_dir . '/' . $file_obj->getFileName());
            }
            unset($file_obj);
        }
    }

    /**
    * get array of (two) new created questions for
    * import id
    */
    public function getImportMapping(): array
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function canEditEctsGrades(): bool
    {
        return $this->canShowEctsGrades() && $this->canEditMarks();
    }

    /**
     * {@inheritdoc}
     */
    public function canShowEctsGrades(): bool
    {
        return (bool) $this->getReportingDate();
    }

    /**
     * {@inheritdoc}
     */
    public function getECTSGrade($passed_array, $reached_points, $max_points): string
    {
        return self::_getECTSGrade($passed_array, $reached_points, $max_points, $this->ects_grades["A"], $this->ects_grades["B"], $this->ects_grades["C"], $this->ects_grades["D"], $this->ects_grades["E"], $this->ects_fx);
    }

    /**
     * {@inheritdoc}
     */
    public static function _getECTSGrade($points_passed, $reached_points, $max_points, $a, $b, $c, $d, $e, $fx): string
    {
        // calculate the median
        $passed_statistics = new ilStatistics();
        $passed_statistics->setData($points_passed);
        $ects_percentiles = array(
            "A" => $passed_statistics->quantile($a),
            "B" => $passed_statistics->quantile($b),
            "C" => $passed_statistics->quantile($c),
            "D" => $passed_statistics->quantile($d),
            "E" => $passed_statistics->quantile($e)
        );
        if (count($points_passed) && ($reached_points >= $ects_percentiles["A"])) {
            return "A";
        } elseif (count($points_passed) && ($reached_points >= $ects_percentiles["B"])) {
            return "B";
        } elseif (count($points_passed) && ($reached_points >= $ects_percentiles["C"])) {
            return "C";
        } elseif (count($points_passed) && ($reached_points >= $ects_percentiles["D"])) {
            return "D";
        } elseif (count($points_passed) && ($reached_points >= $ects_percentiles["E"])) {
            return "E";
        } elseif (strcmp($fx, "") != 0) {
            if ($max_points > 0) {
                $percentage = ($reached_points / $max_points) * 100.0;
                if ($percentage < 0) {
                    $percentage = 0.0;
                }
            } else {
                $percentage = 0.0;
            }
            if ($percentage >= $fx) {
                return "FX";
            } else {
                return "F";
            }
        } else {
            return "F";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkMarks()
    {
        return $this->mark_schema->checkMarks();
    }

    /**
     * {@inheritdoc}
     */
    public function getMarkSchema(): ASS_MarkSchema
    {
        return $this->mark_schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getMarkSchemaForeignId(): int
    {
        return $this->getTestId();
    }

    /**
     */
    public function onMarkSchemaSaved()
    {
        /**
         * @var $tree          ilTree
         * @var $ilDB          ilDBInterface
         */
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $component_repository = $DIC['component.repository'];
        $tree = $DIC['tree'];

        $testQuestionSetConfigFactory = new ilTestQuestionSetConfigFactory($tree, $ilDB, $component_repository, $this);
        $this->saveCompleteStatus($testQuestionSetConfigFactory->getQuestionSetConfig());

        if ($this->participantDataExist()) {
            $this->recalculateScores(true);
        }
    }

    /**
     * @return {@inheritdoc}
     */
    public function canEditMarks(): bool
    {
        $total = $this->evalTotalPersons();
        if ($total > 0) {
            if ($this->getReportingDate()) {
                if (preg_match("/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/", $this->getReportingDate(), $matches)) {
                    $epoch_time = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
                    $now = time();
                    if ($now < $epoch_time) {
                        return true;
                    }
                }
            }
            return false;
        } else {
            return true;
        }
    }

    /**
    * Sets the authors name of the ilObjTest object
    *
    * @param string $author A string containing the name of the test author
    * @access public
    * @see $author
    */
    public function setAuthor($author = "")
    {
        $this->author = $author;
    }

    /**
    * Saves an authors name into the lifecycle metadata if no lifecycle metadata exists
    * This will only be called for conversion of "old" tests where the author hasn't been
    * stored in the lifecycle metadata
    *
    * @param string $a_author A string containing the name of the test author
    * @access private
    * @see $author
    */
    public function saveAuthorToMetadata($a_author = "")
    {
        $md = new ilMD($this->getId(), 0, $this->getType());
        $md_life = $md->getLifecycle();
        if (!$md_life) {
            if (strlen($a_author) == 0) {
                global $DIC;
                $ilUser = $DIC['ilUser'];
                $a_author = $ilUser->getFullname();
            }

            $md_life = $md->addLifecycle();
            $md_life->save();
            $con = $md_life->addContribute();
            $con->setRole("Author");
            $con->save();
            $ent = $con->addEntity();
            $ent->setEntity($a_author);
            $ent->save();
        }
    }

    /**
    * @inheritDoc
    */
    protected function doCreateMetaData(): void
    {
        $this->saveAuthorToMetadata();
    }

    /**
    * Gets the authors name of the ilObjTest object
    *
    * @return string The string containing the name of the test author
    * @access public
    * @see $author
    */
    public function getAuthor(): string
    {
        $author = array();
        $md = new ilMD($this->getId(), 0, $this->getType());
        $md_life = $md->getLifecycle();
        if ($md_life) {
            $ids = $md_life->getContributeIds();
            foreach ($ids as $id) {
                $md_cont = $md_life->getContribute($id);
                if (strcmp($md_cont->getRole(), "Author") == 0) {
                    $entids = $md_cont->getEntityIds();
                    foreach ($entids as $entid) {
                        $md_ent = $md_cont->getEntity($entid);
                        array_push($author, $md_ent->getEntity());
                    }
                }
            }
        }
        return join(",", $author);
    }

    /**
    * Gets the authors name of the ilObjTest object
    *
    * @return string The string containing the name of the test author
    * @access public
    * @see $author
    */
    public static function _lookupAuthor($obj_id): string
    {
        $author = array();
        $md = new ilMD($obj_id, 0, "tst");
        $md_life = $md->getLifecycle();
        if ($md_life) {
            $ids = $md_life->getContributeIds();
            foreach ($ids as $id) {
                $md_cont = $md_life->getContribute($id);
                if (strcmp($md_cont->getRole(), "Author") == 0) {
                    $entids = $md_cont->getEntityIds();
                    foreach ($entids as $entid) {
                        $md_ent = $md_cont->getEntity($entid);
                        array_push($author, $md_ent->getEntity());
                    }
                }
            }
        }
        return join(",", $author);
    }

    /**
    * Returns the available tests for the active user
    *
    * @return array The available tests
    * @access public
    */
    public static function _getAvailableTests($use_object_id = false): array
    {
        global $DIC;
        $ilUser = $DIC['ilUser'];
        $ilDB = $DIC['ilDB'];

        $result_array = array();
        $tests = array_slice(
            array_reverse(
                ilUtil::_getObjectsByOperations("tst", "write", $ilUser->getId(), PHP_INT_MAX)
            ),
            0,
            10000
        );

        if (count($tests)) {
            $titles = ilObject::_prepareCloneSelection($tests, "tst");
            foreach ($tests as $ref_id) {
                if ($use_object_id) {
                    $obj_id = ilObject::_lookupObjId($ref_id);
                    $result_array[$obj_id] = $titles[$ref_id];
                } else {
                    $result_array[$ref_id] = $titles[$ref_id];
                }
            }
        }
        return $result_array;
    }

    /**
    * Clone object
    *
    * @access public
    * @param int ref id of parent container
    * @param int copy id
    * @return object new test object
    */
    public function cloneObject(int $target_id, int $copy_id = 0, bool $omit_tree = false): ?ilObject
    {
        global $DIC;

        /** @noinspection PhpUndefinedMethodInspection */
        $certificateLogger = $DIC->logger()->cert();
        $tree = $DIC['tree'];
        $ilDB = $DIC->database();
        $component_repository = $DIC['component.repository'];

        $this->loadFromDb();

        // Copy settings
        /** @var $newObj ilObjTest */
        $newObj = parent::cloneObject($target_id, $copy_id, $omit_tree);
        $newObj->setTmpCopyWizardCopyId($copy_id);
        $this->cloneMetaData($newObj);

        //copy online status if object is not the root copy object
        $cp_options = ilCopyWizardOptions::_getInstance($copy_id);
        if ($cp_options->isRootNode($this->getRefId())) {
            $newObj->setOfflineStatus(true);
        } else {
            $newObj->setOfflineStatus($this->getOfflineStatus());
        }
        $newObj->update();

        $newObj->setAnonymity($this->getAnonymity());
        $newObj->setAnswerFeedback($this->getAnswerFeedback());
        $newObj->setAnswerFeedbackPoints($this->getAnswerFeedbackPoints());
        $newObj->setAuthor($this->getAuthor());
        $newObj->setLimitUsersEnabled($this->isLimitUsersEnabled());
        $newObj->setAllowedUsers($this->getAllowedUsers());
        $newObj->setAllowedUsersTimeGap($this->getAllowedUsersTimeGap());
        $newObj->setECTSFX($this->getECTSFX());
        $newObj->setECTSGrades($this->getECTSGrades());
        $newObj->setECTSOutput($this->getECTSOutput());
        $newObj->setEnableProcessingTime($this->getEnableProcessingTime());
        $newObj->setEndingTimeEnabled($this->isEndingTimeEnabled());
        $newObj->setEndingTime($this->getEndingTime());
        $newObj->setFixedParticipants($this->getFixedParticipants());
        $newObj->setInstantFeedbackSolution($this->getInstantFeedbackSolution());
        $newObj->setIntroductionEnabled($this->isIntroductionEnabled());
        $newObj->setIntroduction($this->getIntroduction());
        $newObj->setFinalStatement($this->getFinalStatement());
        $newObj->setShowInfo($this->getShowInfo());
        $newObj->setForceJS($this->getForceJS());
        $newObj->setCustomStyle($this->getCustomStyle());
        $newObj->setKiosk($this->getKiosk());
        $newObj->setShowFinalStatement($this->getShowFinalStatement());
        $newObj->setListOfQuestionsSettings($this->getListOfQuestionsSettings());
        $newObj->setMailNotification($this->getMailNotification());
        $newObj->setMailNotificationType($this->getMailNotificationType());
        $newObj->setNrOfTries($this->getNrOfTries());
        $newObj->setBlockPassesAfterPassedEnabled($this->isBlockPassesAfterPassedEnabled());
        $newObj->setPasswordEnabled($this->isPasswordEnabled());
        $newObj->setPassword($this->getPassword());
        $newObj->setProcessingTime($this->getProcessingTime());
        $newObj->setQuestionSetType($this->getQuestionSetType());
        $newObj->setReportingDate($this->getReportingDate());
        $newObj->setResetProcessingTime($this->getResetProcessingTime());
        $newObj->setShowGradingStatusEnabled($this->isShowGradingStatusEnabled());
        $newObj->setShowGradingMarkEnabled($this->isShowGradingMarkEnabled());
        $newObj->setSequenceSettings($this->getSequenceSettings());
        $newObj->setShowCancel($this->getShowCancel());
        $newObj->setShowMarker($this->getShowMarker());
        $newObj->setShuffleQuestions($this->getShuffleQuestions());
        $newObj->setStartingTimeEnabled($this->isStartingTimeEnabled());
        $newObj->setStartingTime($this->getStartingTime());
        $newObj->setTitleOutput($this->getTitleOutput());
        $newObj->setUsePreviousAnswers($this->getUsePreviousAnswers());
        $newObj->setRedirectionMode($this->getRedirectionMode());
        $newObj->setRedirectionUrl($this->getRedirectionUrl());
        $newObj->mark_schema = clone $this->mark_schema;
        $newObj->setEnabledViewMode($this->getEnabledViewMode());
        $newObj->setTemplate($this->getTemplate());
        $newObj->setShowExamIdInTestPassEnabled($this->isShowExamIdInTestPassEnabled());
        $newObj->setEnableExamView($this->getEnableExamview());
        $newObj->setShowExamViewHtml($this->getShowExamviewHtml());
        $newObj->setShowExamViewPdf($this->getShowExamviewPdf());
        $newObj->setEnableArchiving($this->getEnableArchiving());
        $newObj->setSignSubmission($this->getSignSubmission());
        $newObj->setCharSelectorAvailability($this->getCharSelectorAvailability());
        $newObj->setCharSelectorDefinition($this->getCharSelectorDefinition());
        $newObj->setSkillServiceEnabled($this->isSkillServiceEnabled());
        $newObj->setFollowupQuestionAnswerFixationEnabled($this->isFollowupQuestionAnswerFixationEnabled());
        $newObj->setInstantFeedbackAnswerFixationEnabled($this->isInstantFeedbackAnswerFixationEnabled());
        $newObj->setForceInstantFeedbackEnabled($this->isForceInstantFeedbackEnabled());
        $newObj->setAutosave($this->getAutosave());
        $newObj->setAutosaveIval($this->getAutosaveIval());
        $newObj->setOfferingQuestionHintsEnabled($this->isOfferingQuestionHintsEnabled());
        $newObj->setSpecificAnswerFeedback($this->getSpecificAnswerFeedback());
        if ($this->isPassWaitingEnabled()) {
            $newObj->setPassWaiting($this->getPassWaiting());
        }
        $newObj->setObligationsEnabled($this->areObligationsEnabled());
        $newObj->saveToDb();

        // clone certificate
        $pathFactory = new ilCertificatePathFactory();
        $templateRepository = new ilCertificateTemplateDatabaseRepository($ilDB);

        $cloneAction = new ilCertificateCloneAction(
            $ilDB,
            $pathFactory,
            $templateRepository,
            $DIC->filesystem()->web(),
            $certificateLogger,
            new ilCertificateObjectHelper()
        );

        $cloneAction->cloneCertificate($this, $newObj);

        $testQuestionSetConfigFactory = new ilTestQuestionSetConfigFactory($tree, $ilDB, $component_repository, $this);
        $testQuestionSetConfigFactory->getQuestionSetConfig()->cloneQuestionSetRelatedData($newObj);
        $newObj->saveQuestionsToDb();

        $skillLevelThresholdList = new ilTestSkillLevelThresholdList($ilDB);
        $skillLevelThresholdList->setTestId($this->getTestId());
        $skillLevelThresholdList->loadFromDb();
        $skillLevelThresholdList->cloneListForTest($newObj->getTestId());

        $newObj->saveToDb();
        $newObj->updateMetaData();// #14467

        $score_settings = $this->getScoreSettingsRepository()->getForObjFi($this->getId());
        $this->getScoreSettingsRepository()->store(
            $score_settings->withTestId($newObj->getTestId())
        );

        $obj_settings = new ilLPObjSettings($this->getId());
        $obj_settings->cloneSettings($newObj->getId());

        return $newObj;
    }

    public function getQuestionCount(): int
    {
        $num = 0;

        if ($this->isRandomTest()) {
            global $DIC;
            $tree = $DIC['tree'];
            $ilDB = $DIC['ilDB'];
            $component_repository = $DIC['component.repository'];

            $questionSetConfig = new ilTestRandomQuestionSetConfig(
                $tree,
                $ilDB,
                $component_repository,
                $this
            );

            $questionSetConfig->loadFromDb();

            if ($questionSetConfig->isQuestionAmountConfigurationModePerPool()) {
                $sourcePoolDefinitionList = new ilTestRandomQuestionSetSourcePoolDefinitionList(
                    $ilDB,
                    $this,
                    new ilTestRandomQuestionSetSourcePoolDefinitionFactory($ilDB, $this)
                );

                $sourcePoolDefinitionList->loadDefinitions();

                if (is_int($sourcePoolDefinitionList->getQuestionAmount())) {
                    $num = $sourcePoolDefinitionList->getQuestionAmount();
                }
            } elseif (is_int($questionSetConfig->getQuestionAmountPerTest())) {
                $num = $questionSetConfig->getQuestionAmountPerTest();
            }
        } else {
            $this->loadQuestions();
            $num = count($this->questions);
        }

        return $num;
    }

    public function getQuestionCountWithoutReloading(): int
    {
        if ($this->isRandomTest()) {
            return $this->getQuestionCount();
        }
        return count($this->questions);
    }

    /**
    * Logs an action into the Test&Assessment log
    *
    * @param string $logtext The log text
    * @param integer $question_id If given, saves the question id to the database
    * @access public
    */
    public function logAction($logtext = "", $question_id = "")
    {
        global $DIC;
        $ilUser = $DIC['ilUser'];

        $original_id = "";
        if (strcmp($question_id, "") != 0) {
            $original_id = assQuestion::_getOriginalId($question_id);
        }
        ilObjAssessmentFolder::_addLog($ilUser->getId(), $this->getId(), $logtext, $question_id, $original_id, true, $this->getRefId());
    }

    /**
    * Returns the ILIAS test object id for a given test id
    *
    * @param integer $test_id The test id
    * @return mixed The ILIAS test object id or FALSE if the query was not successful
    * @access public
    */
    public static function _getObjectIDFromTestID($test_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $object_id = false;
        $result = $ilDB->queryF(
            "SELECT obj_fi FROM tst_tests WHERE test_id = %s",
            array('integer'),
            array($test_id)
        );
        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            $object_id = $row["obj_fi"];
        }
        return $object_id;
    }

    /**
    * Returns the ILIAS test object id for a given active id
    *
    * @param integer $active_id The active id
    * @return mixed The ILIAS test object id or FALSE if the query was not successful
    * @access public
    */
    public static function _getObjectIDFromActiveID($active_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $object_id = false;
        $result = $ilDB->queryF(
            "SELECT tst_tests.obj_fi FROM tst_tests, tst_active WHERE tst_tests.test_id = tst_active.test_fi AND tst_active.active_id = %s",
            array('integer'),
            array($active_id)
        );
        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            $object_id = $row["obj_fi"];
        }
        return $object_id;
    }

    /**
    * Returns the ILIAS test id for a given object id
    *
    * @param integer $object_id The object id
    * @return mixed The ILIAS test id or FALSE if the query was not successful
    * @access public
    */
    public static function _getTestIDFromObjectID($object_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $test_id = false;
        $result = $ilDB->queryF(
            "SELECT test_id FROM tst_tests WHERE obj_fi = %s",
            array('integer'),
            array($object_id)
        );
        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            $test_id = $row["test_id"];
        }
        return $test_id;
    }

    /**
    * Returns the text answer of a given user for a given question
    *
    * @param integer $user_id The user id
    * @param integer $question_id The question id
    * @return string The answer text
    * @access public
    */
    public function getTextAnswer($active_id, $question_id, $pass = null): string
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $res = "";
        if (($active_id) && ($question_id)) {
            if (is_null($pass)) {
                $pass = assQuestion::_getSolutionMaxPass($question_id, $active_id);
            }
            $result = $ilDB->queryF(
                "SELECT value1 FROM tst_solutions WHERE active_fi = %s AND question_fi = %s AND pass = %s",
                array('integer', 'integer', 'integer'),
                array($active_id, $question_id, $pass)
            );
            if ($result->numRows() == 1) {
                $row = $ilDB->fetchAssoc($result);
                $res = $row["value1"];
            }
        }
        return $res;
    }

    /**
    * Returns the question text for a given question
    *
    * @param integer $question_id The question id
    * @return string The question text
    * @access public
    */
    public function getQuestiontext($question_id): string
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $res = "";
        if ($question_id) {
            $result = $ilDB->queryF(
                "SELECT question_text FROM qpl_questions WHERE question_id = %s",
                array('integer'),
                array($question_id)
            );
            if ($result->numRows() == 1) {
                $row = $ilDB->fetchAssoc($result);
                $res = $row["question_text"];
            }
        }
        return $res;
    }

    /**
     * @return ilTestParticipantList
     */
    public function getInvitedParticipantList(): ilTestParticipantList
    {
        $participantList = new ilTestParticipantList($this);
        $participantList->initializeFromDbRows($this->getInvitedUsers());

        return $participantList;
    }

    /**
     * @return ilTestParticipantList
     */
    public function getActiveParticipantList(): ilTestParticipantList
    {
        $participantList = new ilTestParticipantList($this);
        $participantList->initializeFromDbRows($this->getTestParticipants());

        return $participantList;
    }

    /**
    * Returns a list of all invited users in a test
    *
    * @return array array of invited users
    * @access public
    */
    public function &getInvitedUsers($user_id = "", $order = "login, lastname, firstname"): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result_array = array();

        if ($this->getAnonymity()) {
            if (is_numeric($user_id)) {
                $result = $ilDB->queryF(
                    "SELECT tst_active.active_id, tst_active.tries, usr_id, %s login, %s lastname, %s firstname, tst_invited_user.clientip, " .
                    "tst_active.submitted test_finished, matriculation, COALESCE(tst_active.last_finished_pass, -1) <> tst_active.last_started_pass unfinished_passes  FROM usr_data, tst_invited_user " .
                    "LEFT JOIN tst_active ON tst_active.user_fi = tst_invited_user.user_fi AND tst_active.test_fi = tst_invited_user.test_fi " .
                    "WHERE tst_invited_user.test_fi = %s and tst_invited_user.user_fi=usr_data.usr_id AND usr_data.usr_id=%s " .
                    "ORDER BY $order",
                    array('text', 'text', 'text', 'integer', 'integer'),
                    array("", $this->lng->txt("anonymous"), "", $this->getTestId(), $user_id)
                );
            } else {
                $result = $ilDB->queryF(
                    "SELECT tst_active.active_id, tst_active.tries, usr_id, %s login, %s lastname, %s firstname, tst_invited_user.clientip, " .
                    "tst_active.submitted test_finished, matriculation, COALESCE(tst_active.last_finished_pass, -1) <> tst_active.last_started_pass unfinished_passes  FROM usr_data, tst_invited_user " .
                    "LEFT JOIN tst_active ON tst_active.user_fi = tst_invited_user.user_fi AND tst_active.test_fi = tst_invited_user.test_fi " .
                    "WHERE tst_invited_user.test_fi = %s and tst_invited_user.user_fi=usr_data.usr_id " .
                    "ORDER BY $order",
                    array('text', 'text', 'text', 'integer'),
                    array("", $this->lng->txt("anonymous"), "", $this->getTestId())
                );
            }
        } else {
            if (is_numeric($user_id)) {
                $result = $ilDB->queryF(
                    "SELECT tst_active.active_id, tst_active.tries, usr_id, login, lastname, firstname, tst_invited_user.clientip, " .
                    "tst_active.submitted test_finished, matriculation, COALESCE(tst_active.last_finished_pass, -1) <> tst_active.last_started_pass unfinished_passes  FROM usr_data, tst_invited_user " .
                    "LEFT JOIN tst_active ON tst_active.user_fi = tst_invited_user.user_fi AND tst_active.test_fi = tst_invited_user.test_fi " .
                    "WHERE tst_invited_user.test_fi = %s and tst_invited_user.user_fi=usr_data.usr_id AND usr_data.usr_id=%s " .
                    "ORDER BY $order",
                    array('integer', 'integer'),
                    array($this->getTestId(), $user_id)
                );
            } else {
                $result = $ilDB->queryF(
                    "SELECT tst_active.active_id, tst_active.tries, usr_id, login, lastname, firstname, tst_invited_user.clientip, " .
                    "tst_active.submitted test_finished, matriculation, COALESCE(tst_active.last_finished_pass, -1) <> tst_active.last_started_pass unfinished_passes  FROM usr_data, tst_invited_user " .
                    "LEFT JOIN tst_active ON tst_active.user_fi = tst_invited_user.user_fi AND tst_active.test_fi = tst_invited_user.test_fi " .
                    "WHERE tst_invited_user.test_fi = %s and tst_invited_user.user_fi=usr_data.usr_id " .
                    "ORDER BY $order",
                    array('integer'),
                    array($this->getTestId())
                );
            }
        }
        $result_array = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            $result_array[$row['usr_id']] = $row;
        }
        return $result_array;
    }

    /**
    * Returns a list of all participants in a test
    *
    * @return array The data of the participants
    * @access public
    */
    public function &getTestParticipants(): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        if ($this->getAnonymity()) {
            $query = "
				SELECT	tst_active.active_id,
						tst_active.tries,
						tst_active.user_fi usr_id,
						%s login,
						%s lastname,
						%s firstname,
						tst_active.submitted test_finished,
						usr_data.matriculation,
						usr_data.active,
						tst_active.lastindex,
						COALESCE(tst_active.last_finished_pass, -1) <> tst_active.last_started_pass unfinished_passes
				FROM tst_active
				LEFT JOIN usr_data
				ON tst_active.user_fi = usr_data.usr_id
				WHERE tst_active.test_fi = %s
				ORDER BY usr_data.lastname
			";
            $result = $ilDB->queryF(
                $query,
                array('text', 'text', 'text', 'integer'),
                array("", $this->lng->txt("anonymous"), "", $this->getTestId())
            );
        } else {
            $query = "
				SELECT	tst_active.active_id,
						tst_active.tries,
						tst_active.user_fi usr_id,
						usr_data.login,
						usr_data.lastname,
						usr_data.firstname,
						tst_active.submitted test_finished,
						usr_data.matriculation,
						usr_data.active,
						tst_active.lastindex,
						COALESCE(tst_active.last_finished_pass, -1) <> tst_active.last_started_pass unfinished_passes
				FROM tst_active
				LEFT JOIN usr_data
				ON tst_active.user_fi = usr_data.usr_id
				WHERE tst_active.test_fi = %s
				ORDER BY usr_data.lastname
			";
            $result = $ilDB->queryF(
                $query,
                array('integer'),
                array($this->getTestId())
            );
        }
        $data = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            $data[$row['active_id']] = $row;
        }
        foreach ($data as $index => $participant) {
            if (strlen(trim($participant["firstname"] . $participant["lastname"])) == 0) {
                $data[$index]["lastname"] = $this->lng->txt("deleted_user");
            }
        }
        return $data;
    }

    public function getTestParticipantsForManualScoring($filter = null): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $scoring = ilObjAssessmentFolder::_getManualScoring();
        if (count($scoring) == 0) {
            return array();
        }

        $participants = &$this->getTestParticipants();
        $filtered_participants = array();
        foreach ($participants as $active_id => $participant) {
            $qstType_IN_manScoreableQstTypes = $ilDB->in('qpl_questions.question_type_fi', $scoring, false, 'integer');

            $queryString = "
				SELECT		tst_test_result.manual

				FROM		tst_test_result

				INNER JOIN	qpl_questions
				ON			tst_test_result.question_fi = qpl_questions.question_id

				WHERE		tst_test_result.active_fi = %s
				AND			$qstType_IN_manScoreableQstTypes
			";

            $result = $ilDB->queryF(
                $queryString,
                array("integer"),
                array($active_id)
            );

            $count = $result->numRows();

            if ($count > 0) {
                switch ($filter) {
                    case 1: // only active users
                        if ($participant['active']) {
                            $filtered_participants[$active_id] = $participant;
                        }
                        break;
                    case 2: // only inactive users
                        if (!$participant['active']) {
                            $filtered_participants[$active_id] = $participant;
                        }
                        break;
                    case 3: // all users
                        $filtered_participants[$active_id] = $participant;
                        break;
                    case 4:
                        $assessmentSetting = new ilSetting("assessment");
                        $manscoring_done = $assessmentSetting->get("manscoring_done_" . $active_id);
                        if ($manscoring_done) {
                            $filtered_participants[$active_id] = $participant;
                        }
                        break;
                    case 5:
                        $assessmentSetting = new ilSetting("assessment");
                        $manscoring_done = $assessmentSetting->get("manscoring_done_" . $active_id);
                        if (!$manscoring_done) {
                            $filtered_participants[$active_id] = $participant;
                        }
                        break;
                    case 6:
                        // partially scored participants
                        $found = 0;
                        while ($row = $ilDB->fetchAssoc($result)) {
                            if ($row["manual"]) {
                                $found++;
                            }
                        }
                        if (($found > 0) && ($found < $count)) {
                            $filtered_participants[$active_id] = $participant;
                        }
                        break;
                    default:
                        $filtered_participants[$active_id] = $participant;
                        break;
                }
            }
        }
        return $filtered_participants;
    }

    /**
    * Returns a data of all users specified by id list
    *
    * @return array The user data "usr_id, login, lastname, firstname, clientip" of the users with id as key
    * @access public
    */
    public function getUserData($ids): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        if (!is_array($ids) || count($ids) == 0) {
            return array();
        }

        if ($this->getAnonymity()) {
            $result = $ilDB->queryF(
                "SELECT usr_id, %s login, %s lastname, %s firstname, client_ip clientip FROM usr_data WHERE " . $ilDB->in('usr_id', $ids, false, 'integer') . " ORDER BY login",
                array('text', 'text', 'text'),
                array("", $this->lng->txt("anonymous"), "")
            );
        } else {
            $result = $ilDB->query("SELECT usr_id, login, lastname, firstname, client_ip clientip FROM usr_data WHERE " . $ilDB->in('usr_id', $ids, false, 'integer') . " ORDER BY login");
        }

        $result_array = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            $result_array[$row["usr_id"]] = $row;
        }
        return $result_array;
    }

    public function getGroupData($ids): array
    {
        if (!is_array($ids) || count($ids) == 0) {
            return array();
        }
        $result = array();
        foreach ($ids as $ref_id) {
            $obj_id = ilObject::_lookupObjId($ref_id);
            $result[$ref_id] = array("ref_id" => $ref_id, "title" => ilObject::_lookupTitle($obj_id), "description" => ilObject::_lookupDescription($obj_id));
        }
        return $result;
    }

    public function getRoleData($ids): array
    {
        if (!is_array($ids) || count($ids) == 0) {
            return array();
        }
        $result = array();
        foreach ($ids as $obj_id) {
            $result[$obj_id] = array("obj_id" => $obj_id, "title" => ilObject::_lookupTitle($obj_id), "description" => ilObject::_lookupDescription($obj_id));
        }
        return $result;
    }


    /**
    * Invites all users of a group to a test
    *
    * @param integer $group_id The database id of the invited group
    * @access public
    */
    public function inviteGroup($group_id)
    {
        $group = new ilObjGroup($group_id);
        $members = $group->getGroupMemberIds();
        foreach ($members as $user_id) {
            $this->inviteUser($user_id, ilObjUser::_lookupClientIP($user_id));
        }
    }

    /**
    * Invites all users of a role to a test
    *
    * @param integer $group_id The database id of the invited group
    * @access public
    */
    public function inviteRole($role_id)
    {
        global $DIC;
        $rbacreview = $DIC['rbacreview'];
        $members = $rbacreview->assignedUsers($role_id);
        foreach ($members as $user_id) {
            $this->inviteUser($user_id, ilObjUser::_lookupClientIP($user_id));
        }
    }



    /**
    * Disinvites a user from a test
    *
    * @param integer $user_id The database id of the disinvited user
    * @access public
    */
    public function disinviteUser($user_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $affectedRows = $ilDB->manipulateF(
            "DELETE FROM tst_invited_user WHERE test_fi = %s AND user_fi = %s",
            array('integer', 'integer'),
            array($this->getTestId(), $user_id)
        );
    }

    /**
    * Invites a user to a test
    *
    * @param integer $user_id The database id of the invited user
    * @access public
    */
    public function inviteUser($user_id, $client_ip = "")
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $ilDB->manipulateF(
            "DELETE FROM tst_invited_user WHERE test_fi = %s AND user_fi = %s",
            array('integer', 'integer'),
            array($this->getTestId(), $user_id)
        );
        $ilDB->manipulateF(
            "INSERT INTO tst_invited_user (test_fi, user_fi, clientip, tstamp) VALUES (%s, %s, %s, %s)",
            array('integer', 'integer', 'text', 'integer'),
            array($this->getTestId(), $user_id, (strlen($client_ip)) ? $client_ip : null, time())
        );
    }


    public function setClientIP($user_id, $client_ip)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $affectedRows = $ilDB->manipulateF(
            "UPDATE tst_invited_user SET clientip = %s, tstamp = %s WHERE test_fi=%s and user_fi=%s",
            array('text', 'integer', 'integer', 'integer'),
            array((strlen($client_ip)) ? $client_ip : null, time(), $this->getTestId(), $user_id)
        );
    }

    /**
     * get solved questions
     *
     * @return array of int containing all question ids which have been set solved for the given user and test
     */
    public static function _getSolvedQuestions($active_id, $question_fi = null): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        if (is_numeric($question_fi)) {
            $result = $ilDB->queryF(
                "SELECT question_fi, solved FROM tst_qst_solved WHERE active_fi = %s AND question_fi=%s",
                array('integer', 'integer'),
                array($active_id, $question_fi)
            );
        } else {
            $result = $ilDB->queryF(
                "SELECT question_fi, solved FROM tst_qst_solved WHERE active_fi = %s",
                array('integer'),
                array($active_id)
            );
        }
        $result_array = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            $result_array[$row["question_fi"]] = $row;
        }
        return $result_array;
    }


    /**
     * sets question solved state to value for given user_id
     */
    public function setQuestionSetSolved($value, $question_id, $user_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $active_id = $this->getActiveIdOfUser($user_id);
        $ilDB->manipulateF(
            "DELETE FROM tst_qst_solved WHERE active_fi = %s AND question_fi = %s",
            array('integer', 'integer'),
            array($active_id, $question_id)
        );
        $ilDB->manipulateF(
            "INSERT INTO tst_qst_solved (solved, question_fi, active_fi) VALUES (%s, %s, %s)",
            array('integer', 'integer', 'integer'),
            array($value, $question_id, $active_id)
        );
    }

    /**
     * returns if the active for user_id has been submitted
     */
    public function isTestFinished($active_id): bool
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT submitted FROM tst_active WHERE active_id=%s AND submitted=%s",
            array('integer', 'integer'),
            array($active_id, 1)
        );
        return $result->numRows() == 1;
    }

    /**
     * returns if the active for user_id has been submitted
     */
    public function isActiveTestSubmitted($user_id = null): bool
    {
        global $DIC;
        $ilUser = $DIC['ilUser'];
        $ilDB = $DIC['ilDB'];

        if (!is_numeric($user_id)) {
            $user_id = $ilUser->getId();
        }

        $result = $ilDB->queryF(
            "SELECT submitted FROM tst_active WHERE test_fi=%s AND user_fi=%s AND submitted=%s",
            array('integer', 'integer', 'integer'),
            array($this->getTestId(), $user_id, 1)
        );
        return $result->numRows() == 1;
    }

    /**
     * returns if the numbers of tries have to be checked
     */
    public function hasNrOfTriesRestriction(): bool
    {
        return $this->getNrOfTries() != 0;
    }


    /**
     * returns if number of tries are reached
     * @deprecated: tries field differs per situation, outside a pass it's the number of tries, inside a pass it's the current pass number.
     */

    public function isNrOfTriesReached($tries): bool
    {
        return $tries >= $this->getNrOfTries();
    }


    /**
     * returns all test results for all participants
     *
     * @param array $partipants array of user ids
     * @param boolean if true, the result will be prepared for csv output (see processCSVRow)
     *
     * @return array of fields, see code for column titles
     */
    public function getAllTestResults($participants, $prepareForCSV = true): array
    {
        $results = array();
        $row = array(
            "user_id" => $this->lng->txt("user_id"),
            "matriculation" => $this->lng->txt("matriculation"),
            "lastname" => $this->lng->txt("lastname"),
            "firstname" => $this->lng->txt("firstname"),
            "login" => $this->lng->txt("login"),
            "reached_points" => $this->lng->txt("tst_reached_points"),
            "max_points" => $this->lng->txt("tst_maximum_points"),
            "percent_value" => $this->lng->txt("tst_percent_solved"),
            "mark" => $this->lng->txt("tst_mark"),
            "ects" => $this->lng->txt("ects_grade"),
            "passed" => $this->lng->txt("tst_mark_passed"),
        );
        $results[] = $row;
        if (count($participants)) {
            if ($this->getECTSOutput()) {
                $passed_array = &$this->getTotalPointsPassedArray();
            }
            foreach ($participants as $active_id => $user_rec) {
                $mark = $ects_mark = '';
                $row = array();
                $reached_points = 0;
                $max_points = 0;
                $pass = ilObjTest::_getResultPass($active_id);
                foreach ($this->questions as $value) {
                    $question = ilObjTest::_instanciateQuestion($value);
                    if (is_object($question)) {
                        $max_points += $question->getMaximumPoints();
                        $reached_points += $question->getReachedPoints($active_id, $pass);
                    }
                }
                if ($max_points > 0) {
                    $percentvalue = $reached_points / $max_points;
                    if ($percentvalue < 0) {
                        $percentvalue = 0.0;
                    }
                } else {
                    $percentvalue = 0;
                }
                $mark_obj = $this->mark_schema->getMatchingMark($percentvalue * 100);
                $passed = "";
                if ($mark_obj) {
                    $mark = $mark_obj->getOfficialName();
                    if ($this->getECTSOutput()) {
                        $ects_mark = $this->getECTSGrade($passed_array, $reached_points, $max_points);
                    }
                }
                if ($this->getAnonymity()) {
                    $user_rec['firstname'] = "";
                    $user_rec['lastname'] = $this->lng->txt("anonymous");
                }
                $row = array(
                    "user_id" => $user_rec['usr_id'],
                    "matriculation" => $user_rec['matriculation'],
                    "lastname" => $user_rec['lastname'],
                    "firstname" => $user_rec['firstname'],
                    "login" => $user_rec['login'],
                    "reached_points" => $reached_points,
                    "max_points" => $max_points,
                    "percent_value" => $percentvalue,
                    "mark" => $mark,
                    "ects" => $ects_mark,
                    "passed" => $user_rec['passed'] ? '1' : '0',
                );
                $results[] = $prepareForCSV ? $this->processCSVRow($row, true) : $row;
            }
        }
        return $results;
    }

    /**
    * Processes an array as a CSV row and converts the array values to correct CSV
    * values. The "converted" array is returned
    *
    * @param array $row The array containing the values for a CSV row
    * @param string $quoteAll Indicates to quote every value (=TRUE) or only values containing quotes and separators (=FALSE, default)
    * @param string $separator The value separator in the CSV row (used for quoting) (; = default)
    * @return array The converted array ready for CSV use
    * @access public
    */
    public function &processCSVRow($row, $quoteAll = false, $separator = ";"): array
    {
        $resultarray = array();
        foreach ($row as $rowindex => $entry) {
            $surround = false;
            if ($quoteAll) {
                $surround = true;
            }
            if (strpos($entry, "\"") !== false) {
                $entry = str_replace("\"", "\"\"", $entry);
                $surround = true;
            }
            if (strpos($entry, $separator) !== false) {
                $surround = true;
            }
            // replace all CR LF with LF (for Excel for Windows compatibility
            $entry = str_replace(chr(13) . chr(10), chr(10), $entry);

            if ($surround) {
                $entry = "\"" . $entry . "\"";
            }

            $resultarray[$rowindex] = $entry;
        }
        return $resultarray;
    }

    /**
    * Retrieves the actual pass of a given user for a given test
    *
    * @param integer $user_id The user id
    * @param integer $test_id The test id
    * @return integer The pass of the user for the given test
    * @access public
    */
    public static function _getPass($active_id): int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $result = $ilDB->queryF(
            "SELECT tries FROM tst_active WHERE active_id = %s",
            array('integer'),
            array($active_id)
        );
        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            return $row["tries"];
        } else {
            return 0;
        }
    }

    /**
    * Retrieves the maximum pass of a given user for a given test
    * in which the user answered at least one question
    *
    * @param integer $user_id The user id
    * @param integer $test_id The test id
    * @return integer The pass of the user for the given test
    * @access public
    */
    public static function _getMaxPass($active_id): ?int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $result = $ilDB->queryF(
            "SELECT MAX(pass) maxpass FROM tst_pass_result WHERE active_fi = %s",
            array('integer'),
            array($active_id)
        );

        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            return $row["maxpass"];
        }

        return null;
    }

    /**
     * Retrieves the best pass of a given user for a given test
     * @param int $active_id
     * @return int|mixed
     */
    public static function _getBestPass($active_id): ?int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT * FROM tst_pass_result WHERE active_fi = %s",
            array('integer'),
            array($active_id)
        );

        if (!$result->numRows()) {
            return null;
        }

        $bestrow = null;
        $bestfactor = 0.0;
        while ($row = $ilDB->fetchAssoc($result)) {
            if ($row["maxpoints"] > 0.0) {
                $factor = (float) ($row["points"] / $row["maxpoints"]);
            } else {
                $factor = 0.0;
            }
            if ($factor === 0.0 && $bestfactor === 0.0
                || $factor > $bestfactor) {
                $bestrow = $row;
                $bestfactor = $factor;
            }
        }

        if (is_array($bestrow)) {
            return $bestrow["pass"];
        }

        return null;
    }

    /**
    * Retrieves the pass number that should be counted for a given user
    *
    * @param integer $user_id The user id
    * @param integer $test_id The test id
    * @return integer The result pass of the user for the given test
    * @access public
    */
    public static function _getResultPass($active_id): ?int
    {
        $counted_pass = null;
        if (ilObjTest::_getPassScoring($active_id) == SCORE_BEST_PASS) {
            $counted_pass = ilObjTest::_getBestPass($active_id);
        } else {
            $counted_pass = ilObjTest::_getMaxPass($active_id);
        }
        return $counted_pass;
    }

    /**
    * Retrieves the number of answered questions for a given user in a given test
    *
    * @param integer $user_id The user id
    * @param integer $test_id The test id
    * @param integer $pass The pass of the test (optional)
    * @return integer The number of answered questions
    * @access public
    */
    public function getAnsweredQuestionCount($active_id, $pass = null): int
    {
        if ($this->isDynamicTest()) {
            global $DIC;
            $tree = $DIC['tree'];
            $ilDB = $DIC['ilDB'];
            $lng = $DIC['lng'];
            $component_repository = $DIC['component.repository'];

            $testSessionFactory = new ilTestSessionFactory($this);
            $testSession = $testSessionFactory->getSession($active_id);

            $testSequenceFactory = new ilTestSequenceFactory($ilDB, $lng, $component_repository, $this);
            $testSequence = $testSequenceFactory->getSequenceByTestSession($testSession);

            $dynamicQuestionSetConfig = new ilObjTestDynamicQuestionSetConfig($tree, $ilDB, $component_repository, $this);
            $dynamicQuestionSetConfig->loadFromDb();

            $testSequence->loadFromDb($dynamicQuestionSetConfig);
            $testSequence->loadQuestions($dynamicQuestionSetConfig, new ilTestDynamicQuestionSetFilterSelection());

            return $testSequence->getTrackedQuestionCount();
        }

        if ($this->isRandomTest()) {
            $this->loadQuestions($active_id, $pass);
        }
        $workedthrough = 0;
        foreach ($this->questions as $value) {
            if (assQuestion::_isWorkedThrough($active_id, $value, $pass)) {
                $workedthrough += 1;
            }
        }
        return $workedthrough;
    }

    /**
     * @param int $active_id
     * @param int $pass
     *
     * @return int
     */
    public static function lookupPassResultsUpdateTimestamp($active_id, $pass): int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        if (is_null($pass)) {
            $pass = 0;
        }

        $query = "
			SELECT	tst_pass_result.tstamp pass_res_tstamp,
					tst_test_result.tstamp quest_res_tstamp

			FROM tst_pass_result

			LEFT JOIN tst_test_result
			ON tst_test_result.active_fi = tst_pass_result.active_fi
			AND tst_test_result.pass = tst_pass_result.pass

			WHERE tst_pass_result.active_fi = %s
			AND tst_pass_result.pass = %s

			ORDER BY tst_test_result.tstamp DESC
		";

        $result = $ilDB->queryF(
            $query,
            array('integer', 'integer'),
            array($active_id, $pass)
        );

        while ($row = $ilDB->fetchAssoc($result)) {
            if ($row['quest_res_tstamp']) {
                return $row['quest_res_tstamp'];
            }

            return $row['pass_res_tstamp'];
        }

        return 0;
    }

    /**
     * Checks if the test is executable by the given user
     * @param         $testSession
     * @param integer $user_id The user id
     * @param bool    $allowPassIncrease
     * @return array Result array
     * @throws ilDateTimeException
     */
    public function isExecutable($testSession, $user_id, $allowPassIncrease = false): array
    {
        $result = array(
            "executable" => true,
            "errormessage" => ""
        );
        if ($this->isDynamicTest()) {
            $result["executable"] = false;
            $result["errormessage"] = ($this->lng->txt("ctm_cannot_be_started"));
            return $result;
        }
        if (!$this->startingTimeReached()) {
            $result["executable"] = false;
            $result["errormessage"] = sprintf($this->lng->txt("detail_starting_time_not_reached"), ilDatePresentation::formatDate(new ilDateTime($this->getStartingTime(), IL_CAL_UNIX)));
            return $result;
        }
        if ($this->endingTimeReached()) {
            $result["executable"] = false;
            $result["errormessage"] = sprintf($this->lng->txt("detail_ending_time_reached"), ilDatePresentation::formatDate(new ilDateTime($this->getEndingTime(), IL_CAL_UNIX)));
            return $result;
        }

        $active_id = $this->getActiveIdOfUser($user_id);

        if ($this->getEnableProcessingTime()) {
            if ($active_id > 0) {
                $starting_time = $this->getStartingTimeOfUser($active_id);
                if ($starting_time !== false) {
                    if ($this->isMaxProcessingTimeReached($starting_time, $active_id)) {
                        if ($allowPassIncrease && $this->getResetProcessingTime() && (($this->getNrOfTries() == 0) || ($this->getNrOfTries() > (self::_getPass($active_id) + 1)))) {
                            // a test pass was quitted because the maximum processing time was reached, but the time
                            // will be resetted for future passes, so if there are more passes allowed, the participant may
                            // start the test again.
                            // This code block is only called when $allowPassIncrease is TRUE which only happens when
                            // the test info page is opened. Otherwise this will lead to unexpected results!
                            $testSession->increasePass();
                            $testSession->setLastSequence(0);
                            $testSession->saveToDb();
                        } else {
                            $result["executable"] = false;
                            $result["errormessage"] = $this->lng->txt("detail_max_processing_time_reached");
                        }
                        return $result;
                    }
                }
            }
        }

        $testPassesSelector = new ilTestPassesSelector($this->db, $this);
        $testPassesSelector->setActiveId($active_id);
        $testPassesSelector->setLastFinishedPass($testSession->getLastFinishedPass());

        if ($this->hasNrOfTriesRestriction() && ($active_id > 0)) {
            $closedPasses = $testPassesSelector->getClosedPasses();

            if (count($closedPasses) >= $this->getNrOfTries()) {
                $result["executable"] = false;
                $result["errormessage"] = $this->lng->txt("maximum_nr_of_tries_reached");
                return $result;
            }

            if ($this->isBlockPassesAfterPassedEnabled() && !$testPassesSelector->openPassExists()) {
                if (ilObjTestAccess::_isPassed($user_id, $this->getId())) {
                    $result['executable'] = false;
                    $result['errormessage'] = $this->lng->txt("tst_addit_passes_blocked_after_passed_msg");
                    return $result;
                }
            }
        }
        if ($this->isPassWaitingEnabled() && $testPassesSelector->getLastFinishedPass() !== null) {
            $lastPass = $testPassesSelector->getLastFinishedPassTimestamp();
            if ($lastPass && strlen($this->getPassWaiting())) {
                $pass_waiting_string = $this->getPassWaiting();
                $time_values = explode(":", $pass_waiting_string);
                $next_pass_allowed = strtotime('+ ' . $time_values[0] . ' Months + ' . $time_values[1] . ' Days + ' . $time_values[2] . ' Hours' . $time_values[3] . ' Minutes', $lastPass);

                if (time() < $next_pass_allowed) {
                    $date = ilDatePresentation::formatDate(new ilDateTime($next_pass_allowed, IL_CAL_UNIX));

                    $result["executable"] = false;
                    $result["errormessage"] = sprintf($this->lng->txt('wait_for_next_pass_hint_msg'), $date);
                    return $result;
                }
            }
        }
        return $result;
    }


    public function canShowTestResults(ilTestSession $testSession): bool
    {
        $passSelector = new ilTestPassesSelector($this->db, $this);

        $passSelector->setActiveId($testSession->getActiveId());
        $passSelector->setLastFinishedPass($testSession->getLastFinishedPass());

        return $passSelector->hasReportablePasses();
    }

    public function hasAnyTestResult(ilTestSession $testSession): bool
    {
        $passSelector = new ilTestPassesSelector($this->db, $this);

        $passSelector->setActiveId($testSession->getActiveId());
        $passSelector->setLastFinishedPass($testSession->getLastFinishedPass());

        return $passSelector->hasExistingPasses();
    }

    /**
    * Returns the unix timestamp of the time a user started a test
    *
    * @param integer $active_id The active id of the user
    * @return false|int The unix timestamp if the user started the test, FALSE otherwise
    * @access public
    */
    public function getStartingTimeOfUser($active_id, $pass = null)
    {
        $ilDB = $this->db;

        if ($active_id < 1) {
            return false;
        }
        if ($pass === null) {
            $pass = ($this->getResetProcessingTime()) ? self::_getPass($active_id) : 0;
        }
        $result = $ilDB->queryF(
            "SELECT tst_times.started FROM tst_times WHERE tst_times.active_fi = %s AND tst_times.pass = %s ORDER BY tst_times.started",
            array('integer', 'integer'),
            array($active_id, $pass)
        );
        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            if (preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $row["started"], $matches)) {
                return mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            } else {
                return time();
            }
        } else {
            return time();
        }
    }

    /**
    * Returns whether the maximum processing time for a test is reached or not
    *
    * @return bool TRUE if the maxium processing time is reached, FALSE if the
    *					maximum processing time is not reached or no maximum processing time is given
    */
    public function isMaxProcessingTimeReached(int $starting_time, int $active_id): bool
    {
        if ($this->getEnableProcessingTime()) {
            $processing_time = $this->getProcessingTimeInSeconds($active_id);
            $now = time();
            if ($now > ($starting_time + $processing_time)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function &getTestQuestions(): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $query = "
			SELECT		questions.*,
						questtypes.type_tag,
						tstquest.sequence,
						tstquest.obligatory,
						origquest.obj_fi orig_obj_fi

			FROM		qpl_questions questions

			INNER JOIN	qpl_qst_type questtypes
			ON			questtypes.question_type_id = questions.question_type_fi

			INNER JOIN	tst_test_question tstquest
			ON			tstquest.question_fi = questions.question_id

			LEFT JOIN	qpl_questions origquest
			ON			origquest.question_id = questions.original_id

			WHERE		tstquest.test_fi = %s

			ORDER BY	tstquest.sequence
		";

        $query_result = $ilDB->queryF(
            $query,
            array('integer'),
            array($this->getTestId())
        );

        $questions = array();

        while ($row = $ilDB->fetchAssoc($query_result)) {
            $question = $row;

            $question['obligationPossible'] = self::isQuestionObligationPossible($row['question_id']);

            $questions[] = $question;
        }

        return $questions;
    }

    /**
     * @param int $questionId
     * @return bool
     */
    public function isTestQuestion($questionId): bool
    {
        foreach ($this->getTestQuestions() as $questionData) {
            if ($questionData['question_id'] != $questionId) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function checkQuestionParent($questionId): bool
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */

        $row = $DIC->database()->fetchAssoc($DIC->database()->queryF(
            "SELECT COUNT(question_id) cnt FROM qpl_questions WHERE question_id = %s AND obj_fi = %s",
            array('integer', 'integer'),
            array($questionId, $this->getId())
        ));

        return (bool) $row['cnt'];
    }

    /**
     * @return float
     */
    public function getFixedQuestionSetTotalPoints()
    {
        $points = 0;

        foreach ($this->getTestQuestions() as $questionData) {
            $points += $questionData['points'];
        }

        return $points;
    }

    /**
     * @return array
     */
    public function getPotentialRandomTestQuestions(): array
    {
        /**
         * @var $ilDB ilDBInterface
         */
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $query = "
			SELECT		questions.*,
						questtypes.type_tag,
						origquest.obj_fi orig_obj_fi

			FROM		qpl_questions questions

			INNER JOIN	qpl_qst_type questtypes
			ON			questtypes.question_type_id = questions.question_type_fi

			INNER JOIN	tst_rnd_cpy tstquest
			ON			tstquest.qst_fi = questions.question_id

			LEFT JOIN	qpl_questions origquest
			ON			origquest.question_id = questions.original_id

			WHERE		tstquest.tst_fi = %s
		";

        $query_result = $ilDB->queryF(
            $query,
            array('integer'),
            array($this->getTestId())
        );

        $questions = array();

        while ($row = $ilDB->fetchAssoc($query_result)) {
            $question = $row;

            $question['obligationPossible'] = self::isQuestionObligationPossible($row['question_id']);

            $questions[] = $question;
        }

        return $questions;
    }

    /**
    * Returns the status of the shuffle_questions variable
    *
    * @return integer 0 if the test questions are not shuffled, 1 if the test questions are shuffled
    * @access public
    */
    public function getShuffleQuestions(): int
    {
        return ($this->shuffle_questions) ? 1 : 0;
    }

    /**
    * Sets the status of the shuffle_questions variable
    *
    * @param boolean $a_shuffle 0 if the test questions are not shuffled, 1 if the test questions are shuffled
    * @access public
    */
    public function setShuffleQuestions($a_shuffle)
    {
        $this->shuffle_questions = ($a_shuffle) ? 1 : 0;
    }

    /**
    * Returns the settings for the list of questions options in the test properties
    * This could contain one of the following values:
    *   0 = No list of questions offered
    *   1 = A list of questions is offered
    *   3 = A list of questions is offered and the list of questions is shown as first page of the test
    *   5 = A list of questions is offered and the list of questions is shown as last page of the test
    *   7 = A list of questions is offered and the list of questions is shown as first and last page of the test
    *
    * @return integer TRUE if the list of questions should be presented, FALSE otherwise
    * @access public
    */
    public function getListOfQuestionsSettings()
    {
        return ($this->show_summary) ? $this->show_summary : 0;
    }

    /**
    * Sets the settings for the list of questions options in the test properties
    * This could contain one of the following values:
    *   0 = No list of questions offered
    *   1 = A list of questions is offered
    *   3 = A list of questions is offered and the list of questions is shown as first page of the test
    *   5 = A list of questions is offered and the list of questions is shown as last page of the test
    *   7 = A list of questions is offered and the list of questions is shown as first and last page of the test
    *
    * @param integer $a_value 0, 1, 3, 5 or 7
    * @access public
    */
    public function setListOfQuestionsSettings($a_value = 0)
    {
        $this->show_summary = $a_value;
    }

    /**
    * Returns if the list of questions should be presented to the user or not
    *
    * @return boolean TRUE if the list of questions should be presented, FALSE otherwise
    * @access public
    */
    public function getListOfQuestions(): bool
    {
        if (($this->show_summary & 1) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Sets if the the list of questions should be presented to the user or not
    *
    * @param boolean $a_value TRUE if the list of questions should be presented, FALSE otherwise
    * @access public
    */
    public function setListOfQuestions($a_value = true)
    {
        if ($a_value) {
            $this->show_summary = 1;
        } else {
            $this->show_summary = 0;
        }
    }

    /**
    * Returns if the list of questions should be presented as the first page of the test
    *
    * @return boolean TRUE if the list of questions is shown as first page of the test, FALSE otherwise
    * @access public
    */
    public function getListOfQuestionsStart(): bool
    {
        if (($this->show_summary & 2) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Sets if the the list of questions as the start page of the test
    *
    * @param boolean $a_value TRUE if the list of questions should be the start page, FALSE otherwise
    * @access public
    */
    public function setListOfQuestionsStart($a_value = true)
    {
        if ($a_value && $this->getListOfQuestions()) {
            $this->show_summary = $this->show_summary | 2;
        }
        if (!$a_value && $this->getListOfQuestions()) {
            if ($this->getListOfQuestionsStart()) {
                $this->show_summary = $this->show_summary ^ 2;
            }
        }
    }

    /**
    * Returns if the list of questions should be presented as the last page of the test
    *
    * @return boolean TRUE if the list of questions is shown as last page of the test, FALSE otherwise
    * @access public
    */
    public function getListOfQuestionsEnd(): bool
    {
        if (($this->show_summary & 4) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Sets if the the list of questions as the end page of the test
    *
    * @param boolean $a_value TRUE if the list of questions should be the end page, FALSE otherwise
    * @access public
    */
    public function setListOfQuestionsEnd($a_value = true)
    {
        if ($a_value && $this->getListOfQuestions()) {
            $this->show_summary = $this->show_summary | 4;
        }
        if (!$a_value && $this->getListOfQuestions()) {
            if ($this->getListOfQuestionsEnd()) {
                $this->show_summary = $this->show_summary ^ 4;
            }
        }
    }

    /**
    * Returns TRUE if the list of questions should be presented with the question descriptions
    */
    public function getListOfQuestionsDescription(): bool
    {
        if (($this->show_summary & 8) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Sets the show_summary attribute to TRUE if the list of questions should be presented with the question descriptions
    *
    * @param boolean $a_value TRUE if the list of questions should be shown with question descriptions, FALSE otherwise
    * @access public
    */
    public function setListOfQuestionsDescription($a_value = true)
    {
        if ($a_value && $this->getListOfQuestions()) {
            $this->show_summary = $this->show_summary | 8;
        }
        if (!$a_value && $this->getListOfQuestions()) {
            if ($this->getListOfQuestionsDescription()) {
                $this->show_summary = $this->show_summary ^ 8;
            }
        }
    }

    /**
    * Returns the combined results presentation value
    *
    * @return integer The combined results presentation value
    * @access public
    */
    public function getResultsPresentation(): int
    {
        return ($this->results_presentation) ? $this->results_presentation : 0;
    }

    /**
    * Returns if the pass details should be shown when a test is not finished
    */
    public function getShowPassDetails(): bool
    {
        return $this->getScoreSettings()->getResultDetailsSettings()->getShowPassDetails();
    }

    /**
    * Returns if the solution details should be presented to the user or not
    */
    public function getShowSolutionDetails(): bool
    {
        return $this->getScoreSettings()->getResultDetailsSettings()->getShowSolutionDetails();
    }

    /**
    * Returns if the solution printview should be presented to the user or not
    */
    public function getShowSolutionPrintview(): bool
    {
        return $this->getScoreSettings()->getResultDetailsSettings()->getShowSolutionPrintview();
    }
    /**
     * @deprecated
     */
    public function canShowSolutionPrintview($user_id = null): bool
    {
        return $this->getShowSolutionPrintview();
    }

    /**
    * Returns if the feedback should be presented to the solution or not
    */
    public function getShowSolutionFeedback(): bool
    {
        return $this->getScoreSettings()->getResultDetailsSettings()->getShowSolutionFeedback();
    }

    /**
    * Returns if the full solution (including ILIAS content) should be presented to the solution or not
    */
    public function getShowSolutionAnswersOnly(): bool
    {
        return $this->getScoreSettings()->getResultDetailsSettings()->getShowSolutionAnswersOnly();
    }

    /**
    * Returns if the signature field should be shown in the test results
    */
    public function getShowSolutionSignature(): bool
    {
        return $this->getScoreSettings()->getResultDetailsSettings()->getShowSolutionSignature();
    }

    /**
    * @return boolean TRUE if the suggested solutions should be shown, FALSE otherwise
    */
    public function getShowSolutionSuggested(): bool
    {
        return $this->getScoreSettings()->getResultDetailsSettings()->getShowSolutionSuggested();
    }

    /**
     * @return boolean TRUE if the results should be compared with the correct results in the list of answers, FALSE otherwise
     * @access public
     */
    public function getShowSolutionListComparison(): bool
    {
        return $this->getScoreSettings()->getResultDetailsSettings()->getShowSolutionListComparison();
    }

    /**
    * Sets the combined results presentation value
    *
    * @param integer $a_results_presentation The combined results presentation value
    * @access public
    * @deprecated
    */
    public function setResultsPresentation($a_results_presentation = 3)
    {
        $this->results_presentation = $a_results_presentation;
    }

    public function getShowSolutionListOwnAnswers($user_id = null): bool
    {
        return $this->getScoreSettings()->getResultDetailsSettings()->getShowSolutionListOwnAnswers();
    }

    /**
     * -------------------------------------------------------------------------
     * Those setters are @deprecated!
     * They modify $results_presentation based on some parameter and fall back to
     * repo-settings when called without.
     * Calls are a.o. in class.ilObjTestGUI.php and here during import;
     * I left it in place to not to break too many things now, but they should go.
     * Soon.
     **/

    /**
    * Sets if the the solution details should be presented to the user or not
    *
    * @param integer $a_details 1 if the solution details should be presented, 0 otherwise
    * @access public
    * @deprecated
    */
    public function setShowSolutionDetails($a_details = 1)
    {
        if ($a_details) {
            $this->results_presentation = $this->results_presentation | 2;
        } else {
            if ($this->getShowSolutionDetails()) {
                $this->results_presentation = $this->results_presentation ^ 2;
            }
        }
    }

    /**
    * Sets if the the solution printview should be presented to the user or not
    *
    * @param boolean $a_details TRUE if the solution printview should be presented, FALSE otherwise
    * @access public
    * @deprecated
    */
    public function setShowSolutionPrintview($a_printview = 1)
    {
        if ($a_printview) {
            $this->results_presentation = $this->results_presentation | 4;
        } else {
            if ($this->getShowSolutionPrintview()) {
                $this->results_presentation = $this->results_presentation ^ 4;
            }
        }
    }

    /**
     * -------------------------------------------------------------------------
     */


    /**
     * @deprecated: use ilTestParticipantData instead
     */
    public static function _getUserIdFromActiveId($active_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $result = $ilDB->queryF(
            "SELECT user_fi FROM tst_active WHERE active_id = %s",
            array('integer'),
            array($active_id)
        );
        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            return $row["user_fi"];
        } else {
            return -1;
        }
    }

    public function isLimitUsersEnabled(): ?bool
    {
        return $this->limitUsersEnabled;
    }

    public function setLimitUsersEnabled(bool $limitUsersEnabled): void
    {
        $this->limitUsersEnabled = $limitUsersEnabled;
    }

    public function getAllowedUsers()
    {
        return ($this->allowedUsers) ? $this->allowedUsers : 0;
    }

    public function setAllowedUsers($a_allowed_users)
    {
        $this->allowedUsers = $a_allowed_users;
    }

    public function getAllowedUsersTimeGap()
    {
        return ($this->allowedUsersTimeGap) ? $this->allowedUsersTimeGap : 0;
    }

    public function setAllowedUsersTimeGap($a_allowed_users_time_gap)
    {
        $this->allowedUsersTimeGap = $a_allowed_users_time_gap;
    }

    public function checkMaximumAllowedUsers(): bool
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $nr_of_users = $this->getAllowedUsers();
        $time_gap = ($this->getAllowedUsersTimeGap()) ? $this->getAllowedUsersTimeGap() : 60;
        if (($nr_of_users > 0) && ($time_gap > 0)) {
            $now = time();
            $time_border = $now - $time_gap;
            $str_time_border = date("YmdHis", $time_border);
            $query = "
				SELECT DISTINCT tst_times.active_fi
				FROM tst_times
				INNER JOIN tst_active
				ON tst_times.active_fi = tst_active.active_id
				AND (
					tst_times.pass > tst_active.last_finished_pass OR tst_active.last_finished_pass IS NULL
				)
				WHERE tst_times.tstamp > %s
				AND tst_active.test_fi = %s
			";
            $result = $ilDB->queryF($query, array('integer', 'integer'), array($time_border, $this->getTestId()));
            if ($result->numRows() >= $nr_of_users) {
                if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
                    $this->logAction($this->lng->txtlng("assessment", "log_could_not_enter_test_due_to_simultaneous_users", ilObjAssessmentFolder::_getLogLanguage()));
                }
                return false;
            } else {
                return true;
            }
        }
        return true;
    }

    public function _getLastAccess($active_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT finished FROM tst_times WHERE active_fi = %s ORDER BY finished DESC",
            array('integer'),
            array($active_id)
        );
        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            return $row["finished"];
        }
        return "";
    }

    public static function lookupLastTestPassAccess($activeId, $passIndex)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $query = "
            SELECT MAX(tst_times.tstamp) as last_pass_access
            FROM tst_times
            WHERE active_fi = %s
            AND pass = %s
        ";

        $res = $DIC->database()->queryF(
            $query,
            array('integer', 'integer'),
            array($activeId, $passIndex)
        );

        while ($row = $DIC->database()->fetchAssoc($res)) {
            return $row['last_pass_access'];
        }

        return null;
    }

    /**
    * Checks if a given string contains HTML or not
    *
    * @param string $a_text Text which should be checked
    * @return boolean
    * @access public
    */
    public function isHTML($a_text): bool
    {
        if (preg_match("/<[^>]*?>/", $a_text)) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Reads an QTI material tag an creates a text string
    *
    * @return string text or xhtml string
    * @access public
    */
    public function QTIMaterialToString($a_material): string
    {
        $result = "";
        for ($i = 0; $i < $a_material->getMaterialCount(); $i++) {
            $material = $a_material->getMaterial($i);
            if (strcmp($material["type"], "mattext") == 0) {
                $result .= $material["material"]->getContent();
            }
            if (strcmp($material["type"], "matimage") == 0) {
                $matimage = $material["material"];
                if (preg_match("/(il_([0-9]+)_mob_([0-9]+))/", $matimage->getLabel(), $matches)) {
                    // import an mediaobject which was inserted using tiny mce
                    if (!is_array(ilSession::get("import_mob_xhtml"))) {
                        ilSession::set("import_mob_xhtml", array());
                    }
                    $import_mob_xhtml = ilSession::get("import_mob_xhtml");
                    array_push($import_mob_xhtml, array("mob" => $matimage->getLabel(), "uri" => $matimage->getUri()));
                }
            }
        }
        global $DIC;
        $ilLog = $DIC['ilLog'];
        $ilLog->write(print_r(ilSession::get("import_mob_xhtml"), true));
        return $result;
    }

    /**
    * Creates a QTI material tag from a plain text or xhtml text
    *
    * @param object $a_xml_writer Reference to the ILIAS XML writer
    * @param string $a_material plain text or html text containing the material
    */
    public function addQTIMaterial(&$a_xml_writer, $a_material = '')
    {
        $a_xml_writer->xmlStartTag("material");
        $txt = $a_material;
        $attrs = array(
            "texttype" => "text/plain"
        );
        if ($this->isHTML($a_material)) {
            $attrs["texttype"] = "text/xhtml";
            $txt = ilRTE::_replaceMediaObjectImageSrc($a_material, 0);
        }

        $a_xml_writer->xmlElement("mattext", $attrs, $txt);

        $mobs = ilObjMediaObject::_getMobsOfObject("tst:html", $this->getId());
        foreach ($mobs as $mob) {
            $moblabel = "il_" . IL_INST_ID . "_mob_" . $mob;
            if (strpos($a_material, "mm_$mob") !== false) {
                if (ilObjMediaObject::_exists($mob)) {
                    $mob_obj = new ilObjMediaObject($mob);
                    $imgattrs = array(
                        "label" => $moblabel,
                        "uri" => "objects/" . "il_" . IL_INST_ID . "_mob_" . $mob . "/" . $mob_obj->getTitle()
                    );
                }
                $a_xml_writer->xmlElement("matimage", $imgattrs, null);
            }
        }
        $a_xml_writer->xmlEndTag("material");
    }

    /**
    * Prepares a string for a text area output in tests
    *
    * @param string $txt_output String which should be prepared for output
    * @access public
    */
    public function prepareTextareaOutput($txt_output, $prepare_for_latex_output = false, $omitNl2BrWhenTextArea = false)
    {
        if ($txt_output == null) {
            $txt_output = '';
        }
        return ilLegacyFormElementsUtil::prepareTextareaOutput(
            $txt_output,
            $prepare_for_latex_output,
            $omitNl2BrWhenTextArea
        );
    }

    /**
    * Returns the anonymity status of the test
    *
    * @return integer The value for the anonymity status (0 = personalized, 1 = anonymized)
    * @access public
    */
    public function getAnonymity(): int
    {
        return ($this->anonymity) ? 1 : 0;
    }

    /**
    * Sets the anonymity status of the test
    *
    * @param integer $a_value The value for the anonymity status (0 = personalized, 1 = anonymized)
    * @access public
    */
    public function setAnonymity($a_value = 0)
    {
        switch ($a_value) {
            case 1:
                $this->anonymity = 1;
                break;
            default:
                $this->anonymity = 0;
                break;
        }
    }

    /**
    * Returns wheather the cancel test button is shown or not
    *
    * @return integer The value for the show cancel status (0 = don't show, 1 = show)
    * @access public
    */
    public function getShowCancel(): int
    {
        return ($this->show_cancel) ? 1 : 0;
    }

    /**
    * Sets the cancel test button status
    *
    * @param integer $a_value The value for the cancel test status (0 = don't show, 1 = show)
    * @access public
    */
    public function setShowCancel($a_value = 1)
    {
        switch ($a_value) {
            case 1:
                $this->show_cancel = 1;
                break;
            default:
                $this->show_cancel = 0;
                break;
        }
    }

    /**
    * Returns wheather the marker button is shown or not
    *
    * @return integer The value for the marker status (0 = don't show, 1 = show)
    * @access public
    */
    public function getShowMarker(): int
    {
        return ($this->show_marker) ? 1 : 0;
    }

    /**
    * Sets the marker button status
    *
    * @param integer $a_value The value for the marker status (0 = don't show, 1 = show)
    * @access public
    */
    public function setShowMarker($a_value = 1)
    {
        switch ($a_value) {
            case 1:
                $this->show_marker = 1;
                break;
            default:
                $this->show_marker = 0;
                break;
        }
    }

    /**
    * Returns the fixed participants status
    *
    * @return integer The value for the fixed participants status (0 = don't allow, 1 = allow)
    * @access public
    */
    public function getFixedParticipants(): int
    {
        return ($this->fixed_participants) ? 1 : 0;
    }

    /**
    * Sets the fixed participants status
    *
    * @param integer $a_value The value for the fixed participants status (0 = don't allow, 1 = allow)
    * @access public
    */
    public function setFixedParticipants($a_value = 1)
    {
        switch ($a_value) {
            case 1:
                $this->fixed_participants = 1;
                break;
            default:
                $this->fixed_participants = 0;
                break;
        }
    }

    /**
    * Returns the anonymity status of a test with a given object id
    *
    * @param int $a_obj_id The object id of the test object
    * @return integer The value for the anonymity status (0 = personalized, 1 = anonymized)
    * @access public
    */
    public static function _lookupAnonymity($a_obj_id): int
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT anonymity FROM tst_tests WHERE obj_fi = %s",
            array('integer'),
            array($a_obj_id)
        );
        while ($row = $ilDB->fetchAssoc($result)) {
            return $row['anonymity'];
        }
        return 0;
    }

    /**
     * returns the question set type of test relating to passed active id
     *
     * @param integer $activeId
     * @return string $questionSetType
     */
    public static function lookupQuestionSetTypeByActiveId($active_id): ?string
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $query = "
			SELECT		tst_tests.question_set_type
			FROM		tst_active
			INNER JOIN	tst_tests
			ON			tst_active.test_fi = tst_tests.test_id
			WHERE		tst_active.active_id = %s
		";

        $res = $ilDB->queryF($query, array('integer'), array($active_id));

        while ($row = $ilDB->fetchAssoc($res)) {
            return $row['question_set_type'];
        }

        return null;
    }

    /**
    * Returns the random status of a test with a given object id
    *
    * @param int $a_obj_id The object id of the test object
    * @return integer The value for the anonymity status (0 = no random, 1 = random)
    * @access public
     * @deprecated
    */
    public function _lookupRandomTestFromActiveId($active_id): int
    {
        throw new Exception(__METHOD__ . ' is deprecated ... use ilObjTest::lookupQuestionSetTypeByActiveId() instead!');

        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT tst_tests.random_test FROM tst_tests, tst_active WHERE tst_active.active_id = %s AND tst_active.test_fi = tst_tests.test_id",
            array('integer'),
            array($active_id)
        );
        while ($row = $ilDB->fetchAssoc($result)) {
            return $row['random_test'];
        }
        return 0;
    }

    /**
     * Returns the full name of a test user according to the anonymity status
     *
     * @param int $user_id The database ID of the user
     * @param boolean $overwrite_anonymity Indicates if the anonymity status should be ignored
     * @return string The full name of the user or UNKNOWN if the anonymity status is affected
     * @access public
     *
     * @deprecated: use ilTestParticipantData instead
     */
    public function userLookupFullName($user_id, $overwrite_anonymity = false, $sorted_order = false, $suffix = ""): string
    {
        if ($this->getAnonymity() && !$overwrite_anonymity) {
            return $this->lng->txt("anonymous") . $suffix;
        } else {
            $uname = ilObjUser::_lookupName($user_id);
            if (strlen($uname["firstname"] . $uname["lastname"]) == 0) {
                $uname["firstname"] = $this->lng->txt("deleted_user");
            }
            if ($sorted_order) {
                return trim($uname["lastname"] . ", " . $uname["firstname"]) . $suffix;
            } else {
                return trim($uname["firstname"] . " " . $uname["lastname"]) . $suffix;
            }
        }
    }

    /**
    * Returns the "Start the Test" label for the Info page
    *
    * @param int $active_id The active id of the current user
    * @return string The "Start the Test" label
    * @access public
    */
    public function getStartTestLabel($active_id): string
    {
        if ($this->getNrOfTries() == 1) {
            return $this->lng->txt("tst_start_test");
        }
        $active_pass = self::_getPass($active_id);
        $res = $this->getNrOfResultsForPass($active_id, $active_pass);
        if ($res == 0) {
            if ($active_pass == 0) {
                return $this->lng->txt("tst_start_test");
            } else {
                return $this->lng->txt("tst_start_new_test_pass");
            }
        } else {
            return $this->lng->txt("tst_resume_test");
        }
    }

    /**
     * Returns the available test defaults for the active user
     * @return array An array containing the defaults
     * @access public
     */
    public function getAvailableDefaults(): array
    {
        /**
         * @var $ilDB   ilDBInterface
         * @var $ilUser ilObjUser
         */
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $ilUser = $DIC['ilUser'];

        $result = $ilDB->queryF(
            "SELECT * FROM tst_test_defaults WHERE user_fi = %s ORDER BY name ASC",
            array('integer'),
            array($ilUser->getId())
        );
        $defaults = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            $defaults[$row["test_defaults_id"]] = $row;
        }
        return $defaults;
    }

    /**
    * Returns the test defaults for a given id
    *
    * @param integer $test_defaults_id The database id of a test defaults dataset
    * @return array An array containing the test defaults
    * @access public
    */
    public function getTestDefaults($test_defaults_id): ?array
    {
        return self::_getTestDefaults($test_defaults_id);
    }

    public static function _getTestDefaults($test_defaults_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT * FROM tst_test_defaults WHERE test_defaults_id = %s",
            array('integer'),
            array($test_defaults_id)
        );
        if ($result->numRows() == 1) {
            $row = $ilDB->fetchAssoc($result);
            return $row;
        } else {
            return null;
        }
    }

    /**
    * Deletes the defaults for a test
    *
    * @param integer $test_default_id The database ID of the test defaults
    * @access public
    */
    public function deleteDefaults($test_default_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $affectedRows = $ilDB->manipulateF(
            "DELETE FROM tst_test_defaults WHERE test_defaults_id = %s",
            array('integer'),
            array($test_default_id)
        );
    }

    /**
    * Adds the defaults of this test to the test defaults
    *
    * @param string $a_name The name of the test defaults
    * @access public
    */
    public function addDefaults($a_name)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $ilUser = $DIC['ilUser'];
        $testsettings = array(
            "TitleOutput" => $this->getTitleOutput(),
            "PassScoring" => $this->getPassScoring(),
            "IntroEnabled" => $this->isIntroductionEnabled(),
            "Introduction" => $this->getIntroduction(),
            "FinalStatement" => $this->getFinalStatement(),
            "ShowInfo" => $this->getShowInfo(),
            "ForceJS" => $this->getForceJS(),
            "CustomStyle" => $this->getCustomStyle(),
            "ShowFinalStatement" => $this->getShowFinalStatement(),
            "SequenceSettings" => $this->getSequenceSettings(),
            "ScoreReporting" => $this->getScoreReporting(),
            "ScoreCutting" => $this->getScoreCutting(),
            'SpecificAnswerFeedback' => $this->getSpecificAnswerFeedback(),
            'PrintBsWithRes' => (int) $this->isBestSolutionPrintedWithResult(),
            "InstantFeedbackSolution" => $this->getInstantFeedbackSolution(),
            "AnswerFeedback" => $this->getAnswerFeedback(),
            "AnswerFeedbackPoints" => $this->getAnswerFeedbackPoints(),
            "ResultsPresentation" => $this->getResultsPresentation(),
            "Anonymity" => $this->getAnonymity(),
            "ShowCancel" => $this->getShowCancel(),
            "ShowMarker" => $this->getShowMarker(),
            "ReportingDate" => $this->getReportingDate(),
            "NrOfTries" => $this->getNrOfTries(),
            'BlockAfterPassed' => (int) $this->isBlockPassesAfterPassedEnabled(),
            "Shuffle" => $this->getShuffleQuestions(),
            "Kiosk" => $this->getKiosk(),
            "UsePreviousAnswers" => $this->getUsePreviousAnswers(),
            "ProcessingTime" => $this->getProcessingTime(),
            "EnableProcessingTime" => $this->getEnableProcessingTime(),
            "ResetProcessingTime" => $this->getResetProcessingTime(),
            "StartingTimeEnabled" => $this->isStartingTimeEnabled(),
            "StartingTime" => $this->getStartingTime(),
            "EndingTimeEnabled" => $this->isEndingTimeEnabled(),
            "EndingTime" => $this->getEndingTime(),
            "ECTSOutput" => $this->getECTSOutput(),
            "ECTSFX" => $this->getECTSFX(),
            "ECTSGrades" => $this->getECTSGrades(),
            "questionSetType" => $this->getQuestionSetType(),
            "CountSystem" => $this->getCountSystem(),
            "mailnotification" => $this->getMailNotification(),
            "mailnottype" => $this->getMailNotificationType(),
            "exportsettings" => $this->getExportSettings(),
            "ListOfQuestionsSettings" => $this->getListOfQuestionsSettings(),
            'obligations_enabled' => (int) $this->areObligationsEnabled(),
            'offer_question_hints' => (int) $this->isOfferingQuestionHintsEnabled(),
            'pass_deletion_allowed' => (int) $this->isPassDeletionAllowed(),
            'enable_examview' => $this->getEnableExamview(),
            'show_examview_html' => $this->getShowExamviewHtml(),
            'show_examview_pdf' => $this->getShowExamviewPdf(),
            'char_selector_availability' => $this->getCharSelectorAvailability(),
            'char_selector_definition' => $this->getCharSelectorDefinition(),
            'skill_service' => (int) $this->isSkillServiceEnabled(),
            'result_tax_filters' => $this->getResultFilterTaxIds(),
            'show_grading_status' => (int) $this->isShowGradingStatusEnabled(),
            'show_grading_mark' => (int) $this->isShowGradingMarkEnabled(),

            'follow_qst_answer_fixation' => $this->isFollowupQuestionAnswerFixationEnabled(),
            'inst_fb_answer_fixation' => $this->isInstantFeedbackAnswerFixationEnabled(),
            'force_inst_fb' => $this->isForceInstantFeedbackEnabled(),
            'redirection_mode' => $this->getRedirectionMode(),
            'redirection_url' => $this->getRedirectionUrl(),
            'sign_submission' => $this->getSignSubmission(),
            'autosave' => (int) $this->getAutosave(),
            'autosave_ival' => $this->getAutosaveIval(),
            'examid_in_test_pass' => (int) $this->isShowExamIdInTestPassEnabled(),
            'examid_in_test_res' => (int) $this->isShowExamIdInTestResultsEnabled(),

            'enable_archiving' => (int) $this->getEnableArchiving(),
            'password_enabled' => (int) $this->isPasswordEnabled(),
            'password' => (string) $this->getPassword(),
            'fixed_participants' => $this->getFixedParticipants(),
            'limit_users_enabled' => $this->isLimitUsersEnabled(),
            'allowedusers' => $this->getAllowedUsers(),
            'alloweduserstimegap' => $this->getAllowedUsersTimeGap(),
            'activation_limited' => $this->isActivationLimited(),
            'activation_start_time' => $this->getActivationStartingTime(),
            'activation_end_time' => $this->getActivationEndingTime(),
            'activation_visibility' => $this->getActivationVisibility(),
            'highscore_enabled' => $this->getHighscoreEnabled(),
            'highscore_anon' => $this->getHighscoreAnon(),
            'highscore_achieved_ts' => $this->getHighscoreAchievedTS(),
            'highscore_score' => $this->getHighscoreScore(),
            'highscore_percentage' => $this->getHighscorePercentage(),
            'highscore_hints' => $this->getHighscoreHints(),
            'highscore_wtime' => $this->getHighscoreWTime(),
            'highscore_own_table' => $this->getHighscoreOwnTable(),
            'highscore_top_table' => $this->getHighscoreTopTable(),
            'highscore_top_num' => $this->getHighscoreTopNum(),
            'use_previous_answers' => (string) $this->getUsePreviousAnswers(),
            'pass_waiting' => $this->getPassWaiting()
        );

        $next_id = $ilDB->nextId('tst_test_defaults');
        $ilDB->insert(
            'tst_test_defaults',
            array(
                'test_defaults_id' => array('integer', $next_id),
                'name' => array('text', $a_name),
                'user_fi' => array('integer', $ilUser->getId()),
                'defaults' => array('clob', serialize($testsettings)),
                'marks' => array('clob', serialize($this->mark_schema)),
                'tstamp' => array('integer', time())
            )
        );
    }

    /**
     * Applies given test defaults to this test
     *
     * @param array $test_default The test defaults database id.
     *
     * @return boolean TRUE if the application succeeds, FALSE otherwise
     */
    public function applyDefaults($test_defaults): bool
    {
        $testsettings = unserialize($test_defaults["defaults"]);
        $this->mark_schema = unserialize($test_defaults["marks"]);

        $this->setTitleOutput($testsettings["TitleOutput"]);
        $this->setIntroductionEnabled($testsettings["IntroEnabled"]);
        $this->setIntroduction($testsettings["Introduction"] ?? '');
        $this->setFinalStatement($testsettings["FinalStatement"] ?? '');
        $this->setShowInfo($testsettings["ShowInfo"]);
        $this->setForceJS($testsettings["ForceJS"]);
        $this->setCustomStyle($testsettings["CustomStyle"]);
        $this->setShowFinalStatement($testsettings["ShowFinalStatement"]);
        $this->setSequenceSettings($testsettings["SequenceSettings"]);
        $this->setScoreReporting($testsettings["ScoreReporting"]);
        $this->setSpecificAnswerFeedback($testsettings['SpecificAnswerFeedback']);
        $this->setInstantFeedbackSolution($testsettings["InstantFeedbackSolution"]);
        $this->setAnswerFeedback($testsettings["AnswerFeedback"]);
        $this->setAnswerFeedbackPoints($testsettings["AnswerFeedbackPoints"]);
        $this->setResultsPresentation($testsettings["ResultsPresentation"]);
        $this->setAnonymity($testsettings["Anonymity"]);
        $this->setShowCancel($testsettings["ShowCancel"]);
        $this->setShuffleQuestions($testsettings["Shuffle"]);
        $this->setShowMarker($testsettings["ShowMarker"]);
        $this->setReportingDate($testsettings["ReportingDate"]);
        $this->setNrOfTries($testsettings["NrOfTries"]);
        $this->setBlockPassesAfterPassedEnabled((bool) $testsettings['BlockAfterPassed']);
        $this->setUsePreviousAnswers($testsettings["UsePreviousAnswers"]);
        $this->setRedirectionMode($testsettings['redirection_mode']);
        $this->setRedirectionUrl($testsettings['redirection_url']);
        $this->setProcessingTime($testsettings["ProcessingTime"]);
        $this->setResetProcessingTime($testsettings["ResetProcessingTime"]);
        $this->setEnableProcessingTime($testsettings["EnableProcessingTime"]);
        $this->setStartingTimeEnabled($testsettings["StartingTimeEnabled"]);
        $this->setStartingTime($testsettings["StartingTime"]);
        $this->setKiosk($testsettings["Kiosk"]);
        $this->setEndingTimeEnabled($testsettings["EndingTimeEnabled"]);
        $this->setEndingTime($testsettings["EndingTime"]);
        $this->setECTSOutput($testsettings["ECTSOutput"]);
        $this->setECTSFX($testsettings["ECTSFX"]);
        $this->setECTSGrades($testsettings["ECTSGrades"]);
        if (isset($testsettings["isRandomTest"])) {
            if ($testsettings["isRandomTest"]) {
                $this->setQuestionSetType(self::QUESTION_SET_TYPE_RANDOM);
            } else {
                $this->setQuestionSetType(self::QUESTION_SET_TYPE_FIXED);
            }
        } elseif (isset($testsettings["questionSetType"])) {
            $this->setQuestionSetType($testsettings["questionSetType"]);
        }

        $this->setMailNotification($testsettings["mailnotification"]);
        $this->setMailNotificationType($testsettings["mailnottype"]);
        $this->setExportSettings($testsettings['exportsettings']);
        $this->setListOfQuestionsSettings($testsettings["ListOfQuestionsSettings"]);
        $this->setObligationsEnabled($testsettings["obligations_enabled"]);
        $this->setOfferingQuestionHintsEnabled($testsettings["offer_question_hints"]);

        if (isset($testsettings['examid_in_kiosk'])) {
            $this->setShowExamIdInTestPassEnabled($testsettings['examid_in_kiosk']);
        } else {
            $this->setShowExamIdInTestPassEnabled($testsettings['examid_in_test_pass']);
        }

        $this->setEnableExamview($testsettings['enable_examview']);
        $this->setShowExamviewHtml($testsettings['show_examview_html']);
        $this->setShowExamviewPdf($testsettings['show_examview_pdf']);
        $this->setEnableArchiving($testsettings['enable_archiving']);
        $this->setSignSubmission($testsettings['sign_submission']);
        $this->setCharSelectorAvailability($testsettings['char_selector_availability']);
        $this->setCharSelectorDefinition($testsettings['char_selector_definition']);
        $this->setSkillServiceEnabled((bool) $testsettings['skill_service']);
        $this->setShowGradingStatusEnabled((bool) $testsettings['show_grading_status']);
        $this->setShowGradingMarkEnabled((bool) $testsettings['show_grading_mark']);
        $this->setFollowupQuestionAnswerFixationEnabled($testsettings['follow_qst_answer_fixation']);
        $this->setInstantFeedbackAnswerFixationEnabled($testsettings['inst_fb_answer_fixation']);
        $this->setForceInstantFeedbackEnabled($testsettings['force_inst_fb']);
        $this->setRedirectionMode($testsettings['redirection_mode']);
        $this->setRedirectionUrl($testsettings['redirection_url']);
        $this->setAutosave($testsettings['autosave']);
        $this->setAutosaveIval($testsettings['autosave_ival']);
        $this->setPasswordEnabled($testsettings['password_enabled']);
        $this->setPassword($testsettings['password']);
        $this->setFixedParticipants($testsettings['fixed_participants']);
        $this->setLimitUsersEnabled($testsettings['limit_users_enabled']);
        $this->setAllowedUsers($testsettings['allowedusers']);
        $this->setAllowedUsersTimeGap($testsettings['alloweduserstimegap']);
        $this->setUsePreviousAnswers($testsettings['use_previous_answers']);
        $this->setActivationLimited($testsettings['activation_limited']);
        $this->setActivationStartingTime($testsettings['activation_start_time']);
        $this->setActivationEndingTime($testsettings['activation_end_time']);
        $this->setActivationVisibility($testsettings['activation_visibility']);
        $this->setPassWaiting($testsettings['pass_waiting']);

        $settings = $this->getScoreSettings();
        $exam_id_in_results = false;
        if (array_key_exists('show_exam_id', $testsettings)) {
            $exam_id_in_results = (bool) $testsettings['show_exam_id'];
        } elseif (array_key_exists('examid_in_test_res', $testsettings)) {
            $exam_id_in_results = (bool) $testsettings['examid_in_test_res'];
        }

        $settings = $settings
            ->withScoringSettings(
                $settings->getScoringSettings()
                ->withPassScoring((bool)$testsettings["PassScoring"])
                ->withScoreCutting((bool)$testsettings['ScoreCutting'])
                ->withCountSystem((bool)$testsettings["CountSystem"])
            )
            ->withResultSummarySettings(
                $settings->getResultSummarySettings()
                ->withPassDeletionAllowed($testsettings['pass_deletion_allowed'])
            )
            ->withResultDetailsSettings(
                $settings->getResultDetailsSettings()
                ->withPrintBestSolutionWithResult((bool) $testsettings['PrintBsWithRes'])
                ->withShowExamIdInTestResults($exam_id_in_results)
                ->withTaxonomyFilterIds((array) $testsettings['result_tax_filters'])
            )
            ->withGamificationSettings(
                $settings->getGamificationSettings()
                ->withHighscoreEnabled($testsettings['highscore_enabled'])
                ->withHighscoreAnon($testsettings['highscore_anon'])
                ->withHighscoreAchievedTS($testsettings['highscore_achieved_ts'])
                ->withHighscoreScore($testsettings['highscore_score'])
                ->withHighscorePercentage($testsettings['highscore_percentage'])
                ->withHighscoreHints($testsettings['highscore_hints'])
                ->withHighscoreWTime($testsettings['highscore_wtime'])
                ->withHighscoreOwnTable($testsettings['highscore_own_table'])
                ->withHighscoreTopTable($testsettings['highscore_top_table'])
                ->withHighscoreTopNum($testsettings['highscore_top_num'])
            )
        ;
        $this->getScoreSettingsRepository()->store($settings);
        $this->saveToDb();

        return true;
    }

    /**
    * Convert a print output to XSL-FO
    *
    * @param string $print_output The print output
    * @return string XSL-FO code
    * @access public
    */
    public function processPrintoutput2FO($print_output): string
    {
        if (extension_loaded("tidy")) {
            $config = array(
                "indent" => false,
                "output-xml" => true,
                "numeric-entities" => true
            );
            $tidy = new tidy();
            $tidy->parseString($print_output, $config, 'utf8');
            $tidy->cleanRepair();
            $print_output = tidy_get_output($tidy);
            $print_output = preg_replace("/^.*?(<html)/", "\\1", $print_output);
        } else {
            $print_output = str_replace("&nbsp;", "&#160;", $print_output);
            $print_output = str_replace("&otimes;", "X", $print_output);
        }
        $xsl = file_get_contents("./Modules/Test/xml/question2fo.xsl");

        // additional font support
        global $DIC;
        $xsl = str_replace(
            'font-family="Helvetica, unifont"',
            'font-family="' . $DIC['ilSetting']->get('rpc_pdf_font', 'Helvetica, unifont') . '"',
            $xsl
        );

        $args = array( '/_xml' => $print_output, '/_xsl' => $xsl );
        $xh = xslt_create();
        $params = array();
        $output = xslt_process($xh, "arg:/_xml", "arg:/_xsl", null, $args, $params);
        xslt_error($xh);
        xslt_free($xh);
        return $output;
    }

    /**
    * Delivers a PDF file from XHTML
    *
    * @param string $html The XHTML string
    * @access public
    */
    public function deliverPDFfromHTML($content, $title = null)
    {
        $content = preg_replace("/href=\".*?\"/", "", $content);
        $printbody = new ilTemplate("tpl.il_as_tst_print_body.html", true, true, "Modules/Test");
        $printbody->setVariable("TITLE", ilLegacyFormElementsUtil::prepareFormOutput($this->getTitle()));
        $printbody->setVariable("ADM_CONTENT", $content);
        $printbody->setCurrentBlock("css_file");
        $printbody->setVariable("CSS_FILE", ilUtil::getStyleSheetLocation("filesystem", "delos.css"));
        $printbody->parseCurrentBlock();
        $printoutput = $printbody->get();
        $html = str_replace("href=\"./", "href=\"" . ILIAS_HTTP_PATH . "/", $printoutput);
        $html = preg_replace("/<div id=\"dontprint\">.*?<\\/div>/ims", "", $html);
        if (extension_loaded("tidy")) {
            $config = array(
                "indent" => false,
                "output-xml" => true,
                "numeric-entities" => true
            );
            $tidy = new tidy();
            $tidy->parseString($html, $config, 'utf8');
            $tidy->cleanRepair();
            $html = tidy_get_output($tidy);
            $html = preg_replace("/^.*?(<html)/", "\\1", $html);
        } else {
            $html = str_replace("&nbsp;", "&#160;", $html);
            $html = str_replace("&otimes;", "X", $html);
        }
        $html = preg_replace("/src=\".\\//ims", "src=\"" . ILIAS_HTTP_PATH . "/", $html);
        $this->deliverPDFfromFO($this->processPrintoutput2FO($html), $title);
    }

    /**
    * Delivers a PDF file from a XSL-FO string
    * @param string $fo The XSL-FO string
    * @access public
    */
    public function deliverPDFfromFO($fo, $title = null): bool
    {
        global $DIC;
        $ilLog = $DIC['ilLog'];

        $fo_file = ilFileUtils::ilTempnam() . ".fo";
        $fp = fopen($fo_file, "w");
        fwrite($fp, $fo);
        fclose($fp);

        try {
            $pdf_base64 = ilRpcClientFactory::factory('RPCTransformationHandler')->ilFO2PDF($fo);
            $filename = (strlen($title)) ? $title : $this->getTitle();
            /** @noinspection PhpUndefinedFieldInspection */
            ilUtil::deliverData(
                $pdf_base64->scalar,
                ilFileUtils::getASCIIFilename($filename) . ".pdf",
                "application/pdf"
            );
            return true;
        } catch (Exception $e) {
            $ilLog->write(__METHOD__ . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
    * Retrieves the feedback comment for a question in a test if it is finalized
    *
    * @param integer $active_id Active ID of the user
    * @param integer $question_id Question ID
    * @param integer $pass Pass number
    * @return string The feedback text
    * @access public
    */
    public static function getManualFeedback($active_id, $question_id, $pass): string
    {
        $feedback = "";
        $row = self::getSingleManualFeedback((int) $active_id, (int) $question_id, (int) $pass);

        if ($row !== [] && ($row['finalized_evaluation'] || \ilTestService::isManScoringDone($active_id))) {
            $feedback = $row['feedback'] ?? '';
        }

        return $feedback;
    }

    public static function getSingleManualFeedback(int $active_id, int $question_id, int $pass): array
    {
        global $DIC;

        $ilDB = $DIC->database();
        $row = [];
        $result = $ilDB->queryF(
            "SELECT * FROM tst_manual_fb WHERE active_fi = %s AND question_fi = %s AND pass = %s",
            ['integer', 'integer', 'integer'],
            [$active_id, $question_id, $pass]
        );

        if ($ilDB->numRows($result) === 1) {
            $row = $ilDB->fetchAssoc($result);
            $row['feedback'] = ilRTE::_replaceMediaObjectImageSrc($row['feedback'] ?? '', 1);
        } elseif ($ilDB->numRows($result) > 1) {
            $DIC->logger()->root()->warning(
                "WARNING: Multiple feedback entries on tst_manual_fb for " .
                "active_fi = $active_id , question_fi = $question_id and pass = $pass"
            );
        }

        return $row;
    }

    /**
     * Retrieves the manual feedback for a question in a test
     *
     * @param integer $question_id Question ID
     * @return array The feedback text
     * @access public
     */
    public static function getCompleteManualFeedback(int $question_id): array
    {
        global $DIC;

        $ilDB = $DIC->database();
        $feedback = array();
        $result = $ilDB->queryF(
            "SELECT * FROM tst_manual_fb WHERE question_fi = %s",
            array('integer'),
            array($question_id)
        );

        while ($row = $ilDB->fetchAssoc($result)) {
            $active = $row['active_fi'];
            $pass = $row['pass'];
            $question = $row['question_fi'];

            $row['feedback'] = ilRTE::_replaceMediaObjectImageSrc($row['feedback'] ?? '', 1);

            $feedback[$active][$pass][$question] = $row;
        }

        return $feedback;
    }

    public function saveManualFeedback(
        int $active_id,
        int $question_id,
        int $pass,
        ?string $feedback,
        bool $finalized = false,
        bool $is_single_feedback = false
    ): bool {
        global $DIC;

        $feedback_old = self::getSingleManualFeedback($active_id, $question_id, $pass);

        $finalized_record = (int) ($feedback_old['finalized_evaluation'] ?? 0);
        if ($finalized_record === 0 || ($is_single_feedback && $finalized_record === 1)) {
            $DIC->database()->manipulateF(
                "DELETE FROM tst_manual_fb WHERE active_fi = %s AND question_fi = %s AND pass = %s",
                ['integer', 'integer', 'integer'],
                [$active_id, $question_id, $pass]
            );

            $this->insertManualFeedback($active_id, $question_id, $pass, $feedback, $finalized, $feedback_old);

            if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
                $this->logManualFeedback($active_id, $question_id, $feedback);
            }
        }

        return true;
    }

    private function insertManualFeedback(
        int $active_id,
        int $question_id,
        int $pass,
        ?string $feedback,
        bool $finalized,
        array $feedback_old
    ): void {
        global $DIC;

        $ilDB = $DIC->database();
        $ilUser = $DIC->user();
        $next_id = $ilDB->nextId('tst_manual_fb');
        $user = $ilUser->getId();
        $finalized_time = time();

        $update_default = [
            'manual_feedback_id' => [ 'integer', $next_id],
            'active_fi' => [ 'integer', $active_id],
            'question_fi' => [ 'integer', $question_id],
            'pass' => [ 'integer', $pass],
            'feedback' => [ 'clob', $feedback ? ilRTE::_replaceMediaObjectImageSrc($feedback, 0) : null],
            'tstamp' => [ 'integer', time()]
        ];

        if ($feedback_old !== [] && (int) $feedback_old['finalized_evaluation'] === 1) {
            $user = $feedback_old['finalized_by_usr_id'];
            $finalized_time = $feedback_old['finalized_tstamp'];
        }

        if ($finalized === false) {
            $update_default['finalized_evaluation'] = ['integer', 0];
            $update_default['finalized_by_usr_id'] = ['integer', 0];
            $update_default['finalized_tstamp'] = ['integer', 0];
        } elseif ($finalized === true) {
            $update_default['finalized_evaluation'] = ['integer', 1];
            $update_default['finalized_by_usr_id'] = ['integer', $user];
            $update_default['finalized_tstamp'] = ['integer', $finalized_time];
        }

        $ilDB->insert('tst_manual_fb', $update_default);
    }

    /**
     * Creates a log for the manual feedback
     *
     * @param integer $active_id Active ID of the user
     * @param integer $question_id Question ID
     * @param string  $feedback The feedback text
     */
    private function logManualFeedback($active_id, $question_id, $feedback)
    {
        global $DIC;

        $ilUser = $DIC->user();
        $lng = $DIC->language();
        $username = ilObjTestAccess::_getParticipantData($active_id);

        $this->logAction(
            sprintf(
                $lng->txtlng('assessment', 'log_manual_feedback', ilObjAssessmentFolder::_getLogLanguage()),
                $ilUser->getFullname() . ' (' . $ilUser->getLogin() . ')',
                $username,
                assQuestion::_getQuestionTitle($question_id),
                $feedback
            )
        );
    }

    /**
    * Returns if Javascript should be chosen for drag & drop actions
    * for the active user
    *
    * @return boolean TRUE if Javascript should be chosen, FALSE otherwise
    * @access public
    */
    public function getJavaScriptOutput(): bool
    {
        return true;
    }

    public function &createTestSequence($active_id, $pass, $shuffle)
    {
        $this->testSequence = new ilTestSequence($active_id, $pass, $this->isRandomTest());
    }

    /**
    * Sets the test ID
    *
    * @param integer $a_id Test ID
    */
    public function setTestId($a_id)
    {
        $this->test_id = $a_id;
    }

    /**
     * returns all test results for all participants
     *
     * @param array $partipants array of user ids
     * @param boolean if true, the result will be prepared for csv output (see processCSVRow)
     *
     * @return array of fields, see code for column titles
     */
    public function getDetailedTestResults($participants): array
    {
        $results = array();
        if (count($participants)) {
            foreach ($participants as $active_id => $user_rec) {
                $row = array();
                $reached_points = 0;
                $max_points = 0;
                $pass = ilObjTest::_getResultPass($active_id);
                foreach ($this->questions as $value) {
                    $question = ilObjTest::_instanciateQuestion($value);
                    if (is_object($question)) {
                        $max_points += $question->getMaximumPoints();
                        $reached_points += $question->getReachedPoints($active_id, $pass);
                        if ($max_points > 0) {
                            $percentvalue = $reached_points / $max_points;
                            if ($percentvalue < 0) {
                                $percentvalue = 0.0;
                            }
                        } else {
                            $percentvalue = 0;
                        }
                        if ($this->getAnonymity()) {
                            $user_rec['firstname'] = "";
                            $user_rec['lastname'] = $this->lng->txt("anonymous");
                        }
                        $row = array(
                            "user_id" => $user_rec['usr_id'],
                            "matriculation" => $user_rec['matriculation'],
                            "lastname" => $user_rec['lastname'],
                            "firstname" => $user_rec['firstname'],
                            "login" => $user_rec['login'],
                            "question_id" => $question->getId(),
                            "question_title" => $question->getTitle(),
                            "reached_points" => $reached_points,
                            "max_points" => $max_points,
                            "passed" => $user_rec['passed'] ? '1' : '0',
                        );
                        $results[] = $row;
                    }
                }
            }
        }
        return $results;
    }

    /**
    * Get test Object ID for question ID
    */
    public static function _lookupTestObjIdForQuestionId($a_q_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT t.obj_fi obj_id FROM tst_test_question q, tst_tests t WHERE q.test_fi = t.test_id AND q.question_fi = %s",
            array('integer'),
            array($a_q_id)
        );
        $rec = $ilDB->fetchAssoc($result);
        return $rec["obj_id"] ?? null;
    }

    /**
    * Checks wheather or not a question plugin with a given name is active
    *
    * @param string $a_pname The plugin name
    * @access public
    */
    public function isPluginActive($a_pname): bool
    {
        global $DIC;
        $component_repository = $DIC['component.repository'];

        if (!$component_repository->getComponentByTypeAndName(
            ilComponentInfo::TYPE_MODULES,
            'TestQuestionPool'
        )->getPluginSlotById('qst')->hasPluginName($a_pname)) {
            return false;
        }

        return $component_repository
            ->getComponentByTypeAndName(
                ilComponentInfo::TYPE_MODULES,
                'TestQuestionPool'
            )
            ->getPluginSlotById(
                'qst'
            )
            ->getPluginByName(
                $a_pname
            )->isActive();
    }

    public function getPassed($active_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT passed FROM tst_result_cache WHERE active_fi = %s",
            array('integer'),
            array($active_id)
        );
        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            return $row['passed'];
        } else {
            $counted_pass = ilObjTest::_getResultPass($active_id);
            $result_array = &$this->getTestResult($active_id, $counted_pass);
            return $result_array["test"]["passed"];
        }
    }

    /**
     * Creates an associated array with all active id's for a given test and original question id
     */
    public function getParticipantsForTestAndQuestion($test_id, $question_id): array
    {
        /** @var ilDBInterface $ilDB */
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $query = "
			SELECT tst_test_result.active_fi, tst_test_result.question_fi, tst_test_result.pass
			FROM tst_test_result
			INNER JOIN tst_active ON tst_active.active_id = tst_test_result.active_fi AND tst_active.test_fi = %s
			INNER JOIN qpl_questions ON qpl_questions.question_id = tst_test_result.question_fi
			LEFT JOIN usr_data ON usr_data.usr_id = tst_active.user_fi
			WHERE tst_test_result.question_fi = %s
			ORDER BY usr_data.lastname ASC, usr_data.firstname ASC
		";

        $result = $ilDB->queryF(
            $query,
            array('integer', 'integer'),
            array($test_id, $question_id)
        );
        $foundusers = array();
        /** @noinspection PhpAssignmentInConditionInspection */
        while ($row = $ilDB->fetchAssoc($result)) {
            if ($this->getAccessFilteredParticipantList() && !$this->getAccessFilteredParticipantList()->isActiveIdInList($row["active_fi"])) {
                continue;
            }

            if (!array_key_exists($row["active_fi"], $foundusers)) {
                $foundusers[$row["active_fi"]] = array();
            }
            array_push($foundusers[$row["active_fi"]], array("pass" => $row["pass"], "qid" => $row["question_fi"]));
        }
        return $foundusers;
    }

    /**
    * Returns the aggregated test results
    *
    * @access public
    */
    public function getAggregatedResultsData(): array
    {
        $data = &$this->getCompleteEvaluationData();
        $foundParticipants = $data->getParticipants();
        $results = array("overview" => array(), "questions" => array());
        if (count($foundParticipants)) {
            $results["overview"][$this->lng->txt("tst_eval_total_persons")] = count($foundParticipants);
            $total_finished = $data->getTotalFinishedParticipants();
            $results["overview"][$this->lng->txt("tst_eval_total_finished")] = $total_finished;
            $average_time = $this->evalTotalStartedAverageTime($data->getParticipantIds());
            $diff_seconds = $average_time;
            $diff_hours = floor($diff_seconds / 3600);
            $diff_seconds -= $diff_hours * 3600;
            $diff_minutes = floor($diff_seconds / 60);
            $diff_seconds -= $diff_minutes * 60;
            $results["overview"][$this->lng->txt("tst_eval_total_finished_average_time")] = sprintf("%02d:%02d:%02d", $diff_hours, $diff_minutes, $diff_seconds);
            $total_passed = 0;
            $total_passed_reached = 0;
            $total_passed_max = 0;
            $total_passed_time = 0;
            foreach ($foundParticipants as $userdata) {
                if ($userdata->getPassed()) {
                    $total_passed++;
                    $total_passed_reached += $userdata->getReached();
                    $total_passed_max += $userdata->getMaxpoints();
                    $total_passed_time += $userdata->getTimeOfWork();
                }
            }
            $average_passed_reached = $total_passed ? $total_passed_reached / $total_passed : 0;
            $average_passed_max = $total_passed ? $total_passed_max / $total_passed : 0;
            $average_passed_time = $total_passed ? $total_passed_time / $total_passed : 0;
            $results["overview"][$this->lng->txt("tst_eval_total_passed")] = $total_passed;
            $results["overview"][$this->lng->txt("tst_eval_total_passed_average_points")] = sprintf("%2.2f", $average_passed_reached) . " " . strtolower($this->lng->txt("of")) . " " . sprintf("%2.2f", $average_passed_max);
            $average_time = $average_passed_time;
            $diff_seconds = $average_time;
            $diff_hours = floor($diff_seconds / 3600);
            $diff_seconds -= $diff_hours * 3600;
            $diff_minutes = floor($diff_seconds / 60);
            $diff_seconds -= $diff_minutes * 60;
            $results["overview"][$this->lng->txt("tst_eval_total_passed_average_time")] = sprintf("%02d:%02d:%02d", $diff_hours, $diff_minutes, $diff_seconds);
        }

        foreach ($data->getQuestionTitles() as $question_id => $question_title) {
            $answered = 0;
            $reached = 0;
            $max = 0;
            foreach ($foundParticipants as $userdata) {
                for ($i = 0; $i <= $userdata->getLastPass(); $i++) {
                    if (is_object($userdata->getPass($i))) {
                        $question = $userdata->getPass($i)->getAnsweredQuestionByQuestionId($question_id);
                        if (is_array($question)) {
                            $answered++;
                            $reached += $question["reached"];
                            $max += $question["points"];
                        }
                    }
                }
            }
            $percent = $max ? $reached / $max * 100.0 : 0;
            $results["questions"][$question_id] = array(
                $question_title,
                sprintf("%.2f", $answered ? $reached / $answered : 0) . " " . strtolower($this->lng->txt("of")) . " " . sprintf("%.2f", $answered ? $max / $answered : 0),
                sprintf("%.2f", $percent) . "%",
                $answered,
                sprintf("%.2f", $answered ? $reached / $answered : 0),
                sprintf("%.2f", $answered ? $max / $answered : 0),
                $percent / 100.0
            );
        }
        return $results;
    }

    /**
    * Get zipped xml file for test
    */
    public function getXMLZip(): string
    {
        $expFactory = new ilTestExportFactory($this);
        $test_exp = $expFactory->getExporter('xml');
        return $test_exp->buildExportFile();
    }

    public function getMailNotification(): int
    {
        return $this->mailnotification;
    }

    public function setMailNotification($a_notification)
    {
        $this->mailnotification = $a_notification;
    }

    public function sendSimpleNotification($active_id)
    {
        $mail = new ilTestMailNotification();
        $owner_id = $this->getOwner();
        $usr_data = $this->userLookupFullName(ilObjTest::_getUserIdFromActiveId($active_id));
        $mail->sendSimpleNotification($owner_id, $this->getTitle(), $usr_data);
    }

    /**
     * Gets additional user fields that should be shown in the user evaluation
     *
     * @return array An array containing the database fields that should be shown in the evaluation
     */
    public function getEvaluationAdditionalFields(): array
    {
        $table_gui = new ilEvaluationAllTableGUI(new ilObjTestGUI($this->getRefId()), 'outEvaluation', $this->getAnonymity());
        return $table_gui->getSelectedColumns();
    }

    public function sendAdvancedNotification($active_id)
    {
        $mail = new ilTestMailNotification();
        $owner_id = $this->getOwner();
        $usr_data = $this->userLookupFullName(ilObjTest::_getUserIdFromActiveId($active_id));

        $participantList = new ilTestParticipantList($this);
        $participantList->initializeFromDbRows($this->getTestParticipants());

        $expFactory = new ilTestExportFactory($this);
        $exportObj = $expFactory->getExporter('results');
        $exportObj->setForcedAccessFilteredParticipantList($participantList);
        $file = $exportObj->exportToExcel($deliver = false, 'active_id', $active_id, $passedonly = false);
        $fd = new ilFileDataMail(ANONYMOUS_USER_ID);
        $fd->copyAttachmentFile($file, "result_" . $active_id . ".xlsx");
        $file_names[] = "result_" . $active_id . ".xlsx";

        $mail->sendAdvancedNotification($owner_id, $this->getTitle(), $usr_data, $file_names);

        if (count($file_names)) {
            $fd->unlinkFiles($file_names);
            unset($fd);
            @unlink($file);
        }
    }

    public function getResultsForActiveId($active_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $query = "
			SELECT		*
			FROM		tst_result_cache
			WHERE		active_fi = %s
		";

        $result = $ilDB->queryF(
            $query,
            array('integer'),
            array($active_id)
        );

        if (!$result->numRows()) {
            assQuestion::_updateTestResultCache($active_id);

            $query = "
				SELECT		*
				FROM		tst_result_cache
				WHERE		active_fi = %s
			";

            $result = $ilDB->queryF(
                $query,
                array('integer'),
                array($active_id)
            );
        }

        $row = $ilDB->fetchAssoc($result);

        return $row;
    }

    public function getMailNotificationType(): int
    {
        if ($this->mailnottype == 1) {
            return $this->mailnottype;
        } else {
            return 0;
        }
    }

    public function setMailNotificationType($a_type)
    {
        if ($a_type == 1) {
            $this->mailnottype = 1;
        } else {
            $this->mailnottype = 0;
        }
    }

    public function getExportSettings(): int
    {
        return $this->getScoreSettings()->getResultDetailsSettings()->getExportSettings();
    }

    public function setExportSettings($a_settings)
    {
        if ($a_settings) {
            $this->exportsettings = $a_settings;
        } else {
            $this->exportsettings = 0;
        }
    }

    public function getExportSettingsSingleChoiceShort(): bool
    {
        return $this->getScoreSettings()->getResultDetailsSettings()->getExportSettingsSingleChoiceShort();
    }

    public function setExportSettingsSingleChoiceShort($a_settings)
    {
        if ($a_settings) {
            $this->exportsettings = $this->exportsettings | 1;
        } else {
            if ($this->getExportSettingsSingleChoiceShort()) {
                $this->exportsettings = $this->exportsettings ^ 1;
            }
        }
    }

    public function getEnabledViewMode()
    {
        return $this->enabled_view_mode;
    }

    public function setEnabledViewMode($mode)
    {
        $this->enabled_view_mode = $mode;
    }

    public function setTemplate($template_id)
    {
        $this->template_id = (int) $template_id;
    }

    public function getTemplate(): string
    {
        return $this->template_id;
    }

    public function isAnyInstantFeedbackOptionEnabled(): bool
    {
        return (
            $this->getSpecificAnswerFeedback() || $this->getGenericAnswerFeedback() ||
            $this->getAnswerFeedbackPoints() || $this->getInstantFeedbackSolution()
        );
    }

    public function getInstantFeedbackOptionsAsArray(): array
    {
        $values = array();

        if ($this->getSpecificAnswerFeedback()) {
            $values[] = 'instant_feedback_specific';
        }
        if ($this->getGenericAnswerFeedback()) {
            $values[] = 'instant_feedback_generic';
        }
        if ($this->getAnswerFeedbackPoints()) {
            $values[] = 'instant_feedback_points';
        }
        if ($this->getInstantFeedbackSolution()) {
            $values[] = 'instant_feedback_solution';
        }

        return $values;
    }

    public function setInstantFeedbackOptionsByArray($options)
    {
        if (is_array($options)) {
            $this->setGenericAnswerFeedback(in_array('instant_feedback_generic', $options) ? 1 : 0);
            $this->setSpecificAnswerFeedback(in_array('instant_feedback_specific', $options) ? 1 : 0);
            $this->setAnswerFeedbackPoints(in_array('instant_feedback_points', $options) ? 1 : 0);
            $this->setInstantFeedbackSolution(in_array('instant_feedback_solution', $options) ? 1 : 0);
        } else {
            $this->setGenericAnswerFeedback(0);
            $this->setSpecificAnswerFeedback(0);
            $this->setAnswerFeedbackPoints(0);
            $this->setInstantFeedbackSolution(0);
        }
    }

    /**
     * @return ilTestReindexedSequencePositionMap
     */
    public function reindexFixedQuestionOrdering(): ilTestReindexedSequencePositionMap
    {
        global $DIC;
        $tree = $DIC['tree'];
        $db = $DIC['ilDB'];
        $component_repository = $DIC['component.repository'];

        $qscFactory = new ilTestQuestionSetConfigFactory($tree, $db, $component_repository, $this);
        $questionSetConfig = $qscFactory->getQuestionSetConfig();

        /* @var ilTestFixedQuestionSetConfig $questionSetConfig */
        $reindexedSequencePositionMap = $questionSetConfig->reindexQuestionOrdering();

        $this->loadQuestions();

        return $reindexedSequencePositionMap;
    }

    public function setQuestionOrderAndObligations($orders, $obligations)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        asort($orders);

        $i = 0;

        foreach ($orders as $id => $position) {
            $i++;

            $obligatory = (
                isset($obligations[$id]) && $obligations[$id] ? 1 : 0
            );

            $query = "
				UPDATE		tst_test_question
				SET			sequence = %s,
							obligatory = %s
				WHERE		question_fi = %s
			";

            $ilDB->manipulateF(
                $query,
                array('integer', 'integer', 'integer'),
                array($i, $obligatory, $id)
            );
        }

        $this->loadQuestions();
    }

    public function moveQuestionAfter($question_to_move, $question_before)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        if ($question_before) {
            $query = 'SELECT sequence, test_fi FROM tst_test_question WHERE question_fi = %s';
            $types = array('integer');
            $values = array($question_before);
            $rset = $ilDB->queryF($query, $types, $values);
        }

        if (!$question_before || ($rset && !($row = $ilDB->fetchAssoc($rset)))) {
            $row = array(
            'sequence' => 0,
            'test_fi' => $this->getTestId(),
        );
        }

        $update = 'UPDATE tst_test_question SET sequence = sequence + 1 WHERE sequence > %s AND test_fi = %s';
        $types = array('integer', 'integer');
        $values = array($row['sequence'], $row['test_fi']);
        $ilDB->manipulateF($update, $types, $values);

        $update = 'UPDATE tst_test_question SET sequence = %s WHERE question_fi = %s';
        $types = array('integer', 'integer');
        $values = array($row['sequence'] + 1, $question_to_move);
        $ilDB->manipulateF($update, $types, $values);

        $this->reindexFixedQuestionOrdering();
    }

    public function hasQuestionsWithoutQuestionpool(): bool
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $questions = $this->getQuestionTitlesAndIndexes();

        $IN_questions = $ilDB->in('q1.question_id', array_keys($questions), false, 'integer');

        $query = "
			SELECT		count(q1.question_id) cnt

			FROM		qpl_questions q1

			INNER JOIN	qpl_questions q2
			ON			q2.question_id = q1.original_id

			WHERE		$IN_questions
			AND		 	q1.obj_fi = q2.obj_fi
		";

        $rset = $ilDB->query($query);

        $row = $ilDB->fetchAssoc($rset);

        return $row['cnt'] > 0;
    }

    /**
     * Gather all finished tests for user
     *
     * @param int $a_user_id
     * @return array(test id => passed)
     */
    public static function _lookupFinishedUserTests($a_user_id): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT test_fi,MAX(pass) AS pass FROM tst_active" .
            " JOIN tst_pass_result ON (tst_pass_result.active_fi = tst_active.active_id)" .
            " WHERE user_fi=%s" .
            " GROUP BY test_fi",
            array('integer', 'integer'),
            array($a_user_id, 1)
        );
        $all = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            $obj_id = self::_getObjectIDFromTestID($row["test_fi"]);
            $all[$obj_id] = (bool) $row["pass"];
        }
        return $all;
    }
    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function isOnline(): bool
    {
        return $this->online;
    }

    public function setOnline($a_online = true)
    {
        $this->online = (bool) $a_online;
    }

    /**
     * @return null
     */
    public function getOldOnlineStatus()
    {
        return $this->oldOnlineStatus;
    }

    public function setOldOnlineStatus($oldOnlineStatus): void
    {
        $this->oldOnlineStatus = $oldOnlineStatus;
    }

    public function isBestSolutionPrintedWithResult(): bool
    {
        return $this->getScoreSettings()->getResultDetailsSettings()->getPrintBestSolutionWithResult();
    }

    /**
     * returns the fact wether offering hints is enabled or not
     *
     * @return boolean
     */
    public function isOfferingQuestionHintsEnabled(): bool
    {
        return $this->offeringQuestionHintsEnabled ?: false;
    }

    /**
     * sets offering question hints enabled/disabled
     *
     * @param boolean $offeringQuestionHintsEnabled
     */
    public function setOfferingQuestionHintsEnabled($offeringQuestionHintsEnabled)
    {
        $this->offeringQuestionHintsEnabled = (bool) $offeringQuestionHintsEnabled;
    }

    public function setActivationVisibility($a_value)
    {
        $this->activation_visibility = (bool) $a_value;
    }

    public function getActivationVisibility()
    {
        return $this->activation_visibility;
    }

    public function isActivationLimited(): ?bool
    {
        return $this->activation_limited;
    }

    public function setActivationLimited($a_value)
    {
        $this->activation_limited = (bool) $a_value;
    }

    /* GET/SET for highscore feature */
    public function getHighscoreEnabled(): bool
    {
        return $this->getScoreSettings()->getGamificationSettings()->getHighscoreEnabled();
    }

    /**
     * Gets if the highscores should be anonymized per setting.
     *
     * Note: This method will retrieve the setting as set by the user. If you want
     * to figure out, if the highscore is to be shown anonymized or not, with
     * consideration of the global anon switch you should @see isHighscoreAnon().
     *
     * @return bool True, if setting is to anonymize highscores.
     */
    public function getHighscoreAnon(): bool
    {
        return $this->getScoreSettings()->getGamificationSettings()->getHighscoreAnon();
    }

    /**
     * Gets if the highscores should be displayed anonymized.
     *
     * Note: This method considers the global anonymity switch. If you need
     * access to the users setting, @see getHighscoreAnon()
     *
     * @return boolean True, if output is anonymized.
     */
    public function isHighscoreAnon(): bool
    {
        return $this->getAnonymity() == 1 || $this->getHighscoreAnon();
    }

    /**
     * Returns if date and time of the scores achievement should be displayed.
     */
    public function getHighscoreAchievedTS(): bool
    {
        return $this->getScoreSettings()->getGamificationSettings()->getHighscoreAchievedTS();
    }

    /**
     * Gets if the score column should be shown.
     */
    public function getHighscoreScore(): bool
    {
        return $this->getScoreSettings()->getGamificationSettings()->getHighscoreScore();
    }

    /**
     * Gets if the percentage column should be shown.
     */
    public function getHighscorePercentage(): bool
    {
        return $this->getScoreSettings()->getGamificationSettings()->getHighscorePercentage();
    }

    /**
     * Gets, if the column with the number of requested hints should be shown.
     */
    public function getHighscoreHints(): bool
    {
        return $this->getScoreSettings()->getGamificationSettings()->getHighscoreHints();
    }

    /**
     * Gets if the column with the workingtime should be shown.
     */
    public function getHighscoreWTime(): bool
    {
        return $this->getScoreSettings()->getGamificationSettings()->getHighscoreWTime();
    }

    /**
     * Gets if the own rankings table should be shown.
     */
    public function getHighscoreOwnTable(): bool
    {
        return $this->getScoreSettings()->getGamificationSettings()->getHighscoreOwnTable();
    }

    /**
     * Gets, if the top-rankings table should be shown.
     */
    public function getHighscoreTopTable(): bool
    {
        return $this->getScoreSettings()->getGamificationSettings()->getHighscoreTopTable();
    }

    /**
     * Gets the number of entries which are to be shown in the top-rankings table.
     * @return integer Number of entries to be shown in the top-rankings table.
     */
    public function getHighscoreTopNum(int $a_retval = 10): int
    {
        return $this->getScoreSettings()->getGamificationSettings()->getHighscoreTopNum();
    }

    public function getHighscoreMode(): int
    {
        return $this->getScoreSettings()->getGamificationSettings()->getHighScoreMode();
    }
    /* End GET/SET for highscore feature*/

    public function setSpecificAnswerFeedback($specific_answer_feedback)
    {
        switch ($specific_answer_feedback) {
            case 1:
                $this->specific_answer_feedback = 1;
                break;
            default:
                $this->specific_answer_feedback = 0;
                break;
        }
    }

    public function getSpecificAnswerFeedback(): int
    {
        switch ($this->specific_answer_feedback) {
            case 1:
                return 1;
            default:
                return 0;
        }
    }

    /**
     * sets obligations enabled/disabled
     *
     * @param boolean $obligationsEnabled
     */
    public function setObligationsEnabled($obligationsEnabled = true)
    {
        $this->obligationsEnabled = (bool) $obligationsEnabled;
    }

    /**
     * returns the fact wether obligations are enabled or not
     *
     * @return boolean
     */
    public function areObligationsEnabled(): bool
    {
        return (bool) $this->obligationsEnabled;
    }

    /**
     * checks wether the obligation for question with given id is possible or not
     *
     * @param integer $questionId
     * @return boolean $obligationPossible
     */
    public static function isQuestionObligationPossible($questionId): bool
    {
        $classConcreteQuestion = assQuestion::_getQuestionType($questionId);

        assQuestion::_includeClass($classConcreteQuestion, 0);

        // static binder is not at work yet (in PHP < 5.3)
        //$obligationPossible = $classConcreteQuestion::isObligationPossible();
        $obligationPossible = call_user_func(array($classConcreteQuestion, 'isObligationPossible'), $questionId);

        return $obligationPossible;
    }

    /**
     * checks wether the question with given id is marked as obligatory or not
     *
     * @param integer $questionId
     * @return boolean $obligatory
     */
    public static function isQuestionObligatory($question_id): bool
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $rset = $ilDB->queryF('SELECT obligatory FROM tst_test_question WHERE question_fi = %s', array('integer'), array($question_id));

        if ($row = $ilDB->fetchAssoc($rset)) {
            return (bool) $row['obligatory'];
        }

        return false;
    }

    /**
     * checks wether all questions marked as obligatory were answered
     * within the test pass with given testId, activeId and pass index
     *
     * @static
     * @access public
     * @global ilDBInterface $ilDB
     * @param integer $test_id
     * @param integer $active_id
     * @param integer $pass
     * @return boolean $allObligationsAnswered
     */
    public static function allObligationsAnswered($test_id, $active_id, $pass): bool
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $rset = $ilDB->queryF(
            'SELECT obligations_answered FROM tst_pass_result WHERE active_fi = %s AND pass = %s',
            array('integer', 'integer'),
            array($active_id, $pass)
        );

        if ($row = $ilDB->fetchAssoc($rset)) {
            return (bool) $row['obligations_answered'];
        }

        return !self::hasObligations($test_id);
    }

    /**
     * returns the fact wether the test with given test id
     * contains questions markes as obligatory or not
     *
     * @global ilDBInterface $ilDB
     * @param integer $test_id
     * @return boolean $hasObligations
     */
    public static function hasObligations($test_id): bool
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $rset = $ilDB->queryF(
            'SELECT count(*) cnt FROM tst_test_question WHERE test_fi = %s AND obligatory = 1',
            array('integer'),
            array($test_id)
        );

        $row = $ilDB->fetchAssoc($rset);

        return (bool) $row['cnt'] > 0;
    }

    public function setAutosave($autosave)
    {
        $this->autosave = $autosave;
    }

    public function getAutosave(): bool
    {
        return $this->autosave;
    }

    public function setAutosaveIval($autosave_ival)
    {
        $this->autosave_ival = $autosave_ival;
    }

    public function getAutosaveIval(): int
    {
        return $this->autosave_ival;
    }

    public function isPassDeletionAllowed(): bool
    {
        return $this->getScoreSettings()->getResultSummarySettings()->getPassDeletionAllowed();
    }

    #region Examview / PDF Examview
    /**
     * @param boolean $show_examview_html
     */
    public function setShowExamviewHtml($show_examview_html): void
    {
        $this->show_examview_html = $show_examview_html;
    }

    /**
     * @return boolean
     */
    public function getShowExamviewHtml(): bool
    {
        return $this->show_examview_html;
    }

    /**
     * @param boolean $show_examview_pdf
     */
    public function setShowExamviewPdf($show_examview_pdf): void
    {
        $this->show_examview_pdf = $show_examview_pdf;
    }

    /**
     * @return boolean
     */
    public function getShowExamviewPdf(): bool
    {
        return $this->show_examview_pdf;
    }

    /**
     * @param boolean $enable_examview
     */
    public function setEnableExamview($enable_examview): void
    {
        $this->enable_examview = $enable_examview;
    }

    /**
     * @return boolean
     */
    public function getEnableExamview(): bool
    {
        return $this->enable_examview;
    }

    #endregion

    public function setActivationStartingTime($starting_time = null)
    {
        $this->activation_starting_time = $starting_time;
    }

    public function setActivationEndingTime($ending_time = null)
    {
        $this->activation_ending_time = $ending_time;
    }

    public function getActivationStartingTime()
    {
        return (strlen($this->activation_starting_time)) ? $this->activation_starting_time : null;
    }

    public function getActivationEndingTime()
    {
        return (strlen($this->activation_ending_time)) ? $this->activation_ending_time : null;
    }

    /**
     * Note, this function should only be used if absolutely necessary, since it perform joins on tables that
     * tend to grow huge and returns vast amount of data. If possible, use getStartingTimeOfUser($active_id) instead
     *
     * @return array
     */
    public function getStartingTimeOfParticipants(): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $times = array();
        $result = $ilDB->queryF(
            "SELECT tst_times.active_fi, tst_times.started FROM tst_times, tst_active WHERE tst_times.active_fi = tst_active.active_id AND tst_active.test_fi = %s ORDER BY tst_times.tstamp DESC",
            array('integer'),
            array($this->getTestId())
        );
        while ($row = $ilDB->fetchAssoc($result)) {
            $times[$row['active_fi']] = $row['started'];
        }
        return $times;
    }

    public function getTimeExtensionsOfParticipants(): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $times = array();
        $result = $ilDB->queryF(
            "SELECT tst_addtime.active_fi, tst_addtime.additionaltime FROM tst_addtime, tst_active WHERE tst_addtime.active_fi = tst_active.active_id AND tst_active.test_fi = %s",
            array('integer'),
            array($this->getTestId())
        );
        while ($row = $ilDB->fetchAssoc($result)) {
            $times[$row['active_fi']] = $row['additionaltime'];
        }
        return $times;
    }

    public function getExtraTime($active_id)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT additionaltime FROM tst_addtime WHERE active_fi = %s",
            array('integer'),
            array($active_id)
        );
        if ($result->numRows() > 0) {
            $row = $ilDB->fetchAssoc($result);
            return $row['additionaltime'];
        }
        return 0;
    }

    public function addExtraTime($active_id, $minutes)
    {
        $participantData = new ilTestParticipantData($this->db, $this->lng);

        $participantData->setParticipantAccessFilter(
            ilTestParticipantAccessFilter::getManageParticipantsUserFilter($this->getRefId())
        );

        if ($active_id) {
            $participantData->setActiveIdsFilter(array($active_id));
        }

        $participantData->load($this->getTestId());

        foreach ($participantData->getActiveIds() as $active_fi) {
            $result = $this->db->queryF(
                "SELECT active_fi FROM tst_addtime WHERE active_fi = %s",
                array('integer'),
                array($active_fi)
            );

            if ($result->numRows() > 0) {
                $this->db->manipulateF(
                    "DELETE FROM tst_addtime WHERE active_fi = %s",
                    array('integer'),
                    array($active_fi)
                );
            }

            $this->db->manipulateF(
                "UPDATE tst_active SET tries = %s, submitted = %s, submittimestamp = %s WHERE active_id = %s",
                array('integer','integer','timestamp','integer'),
                array(0, 0, null, $active_fi)
            );

            $this->db->manipulateF(
                "INSERT INTO tst_addtime (active_fi, additionaltime, tstamp) VALUES (%s, %s, %s)",
                array('integer','integer','integer'),
                array($active_fi, $minutes, time())
            );

            if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
                $this->logAction(sprintf($this->lng->txtlng("assessment", "log_added_extratime", ilObjAssessmentFolder::_getLogLanguage()), $minutes, $active_id));
            }
        }
    }

    /**
     * @param boolean $enable_archiving
     *
     * @return $this
     */
    public function setEnableArchiving($enable_archiving): ilObjTest
    {
        $this->enable_archiving = $enable_archiving;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getEnableArchiving(): bool
    {
        return $this->enable_archiving;
    }

    public function getMaxPassOfTest(): int
    {
        /**
         * @var $ilDB ilDBInterface
         */
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $query = '
			SELECT MAX(tst_pass_result.pass) + 1 max_res
			FROM tst_pass_result
			INNER JOIN tst_active ON tst_active.active_id = tst_pass_result.active_fi
			WHERE test_fi = ' . $ilDB->quote($this->getTestId(), 'integer') . '
		';
        $res = $ilDB->query($query);
        $data = $ilDB->fetchAssoc($res);
        return (int) $data['max_res'];
    }

    public static function lookupExamId($active_id, $pass)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $exam_id_query = 'SELECT exam_id FROM tst_pass_result WHERE active_fi = %s AND pass = %s';
        $exam_id_result = $ilDB->queryF($exam_id_query, array( 'integer', 'integer' ), array( $active_id, $pass ));
        if ($ilDB->numRows($exam_id_result) == 1) {
            $exam_id_row = $ilDB->fetchAssoc($exam_id_result);

            if ($exam_id_row['exam_id'] != null) {
                return $exam_id_row['exam_id'];
            }
        }

        return null;
    }

    public static function buildExamId($active_id, $pass, $test_obj_id = null): string
    {
        global $DIC;
        $ilSetting = $DIC['ilSetting'];

        $inst_id = $ilSetting->get('inst_id', null);

        if ($test_obj_id === null) {
            $obj_id = self::_getObjectIDFromActiveID($active_id);
        } else {
            $obj_id = $test_obj_id;
        }

        $examId = 'I' . $inst_id . '_T' . $obj_id . '_A' . $active_id . '_P' . $pass;

        return $examId;
    }

    public function setShowExamIdInTestPassEnabled($show_exam_id_in_test_pass_enabled)
    {
        $this->show_exam_id_in_test_pass_enabled = $show_exam_id_in_test_pass_enabled;
    }

    public function isShowExamIdInTestPassEnabled(): bool
    {
        return $this->show_exam_id_in_test_pass_enabled;
    }

    /**
     * @return boolean
     */
    public function isShowExamIdInTestResultsEnabled(): bool
    {
        return $this->getScoreSettings()->getResultDetailsSettings()->getShowExamIdInTestResults();
    }

    /**
     * @param boolean $sign_submission
     */
    public function setSignSubmission($sign_submission)
    {
        $this->sign_submission = $sign_submission;
    }

    /**
     * @return boolean
     */
    public function getSignSubmission(): bool
    {
        return $this->sign_submission;
    }

    public function setCharSelectorAvailability($availability)
    {
        $this->char_selector_availability = (int) $availability;
    }

    /**
     * @return int	availability of the special character selector
     */
    public function getCharSelectorAvailability(): int
    {
        return (int) $this->char_selector_availability;
    }

    /**
     * @param string	definition of the special character selector
     */
    public function setCharSelectorDefinition($definition = '')
    {
        $this->char_selector_definition = $definition;
    }

    /**
     * @return string	definition of the special character selector
     */
    public function getCharSelectorDefinition(): ?string
    {
        return $this->char_selector_definition;
    }


    /**
     * setter for question set type
     *
     * @param string $questionSetType
     */
    public function setQuestionSetType($questionSetType)
    {
        $this->questionSetType = $questionSetType;
    }

    /**
     * getter for question set type
     *
     * @return string $questionSetType
     */
    public function getQuestionSetType(): string
    {
        return $this->questionSetType;
    }

    /**
     * lookup-er for question set type
     *
     * @global ilDBInterface $ilDB
     * @param integer $objId
     * @return string $questionSetType
     */
    public static function lookupQuestionSetType($objId): ?string
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $query = "SELECT question_set_type FROM tst_tests WHERE obj_fi = %s";

        $res = $ilDB->queryF($query, array('integer'), array($objId));

        $questionSetType = null;

        while ($row = $ilDB->fetchAssoc($res)) {
            $questionSetType = $row['question_set_type'];
        }

        return $questionSetType;
    }

    /**
     * Returns the fact wether this test is a fixed question set test or not
     *
     * @return boolean $isFixedTest
     */
    public function isFixedTest(): bool
    {
        return $this->getQuestionSetType() == self::QUESTION_SET_TYPE_FIXED;
    }

    /**
     * Returns the fact wether this test is a random questions test or not
     *
     * @return boolean $isRandomTest
     */
    public function isRandomTest(): bool
    {
        return $this->getQuestionSetType() == self::QUESTION_SET_TYPE_RANDOM;
    }

    /**
     * Returns the fact wether this test is a dynamic question set test or not
     *
     * @deprecated 9 This is only kept in, to avoid crashing dynamic question tests with an ugly error.
     */
    public function isDynamicTest(): bool
    {
        return $this->getQuestionSetType() == self::QUESTION_SET_TYPE_DYNAMIC;
    }

    /**
     * Returns the fact wether the test with passed obj id is a random questions test or not
     *
     * @param integer $a_obj_id
     * @return boolean $isRandomTest
     * @deprecated
     */
    public static function _lookupRandomTest($a_obj_id): bool
    {
        return self::lookupQuestionSetType($a_obj_id) == self::QUESTION_SET_TYPE_RANDOM;
    }

    public function getQuestionSetTypeTranslation(ilLanguage $lng, $questionSetType): string
    {
        switch ($questionSetType) {
            case ilObjTest::QUESTION_SET_TYPE_FIXED:
                return $lng->txt('tst_question_set_type_fixed');

            case ilObjTest::QUESTION_SET_TYPE_RANDOM:
                return $lng->txt('tst_question_set_type_random');
        }

        throw new ilTestException('invalid question set type value given: ' . $questionSetType);
    }

    public function participantDataExist(): bool
    {
        if ($this->participantDataExist === null) {
            $this->participantDataExist = (bool) $this->evalTotalPersons();
        }

        return $this->participantDataExist;
    }

    public function recalculateScores($preserve_manscoring = false)
    {
        $scoring = new ilTestScoring($this);
        $scoring->setPreserveManualScores($preserve_manscoring);
        $scoring->recalculateSolutions();
        ilLPStatusWrapper::_updateStatus($this->getId(), $this->user->getId());
    }

    public static function getTestObjIdsWithActiveForUserId($userId): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $query = "
			SELECT obj_fi
			FROM tst_active
			INNER JOIN tst_tests
			ON test_id = test_fi
			WHERE user_fi = %s
		";

        $res = $ilDB->queryF($query, array('integer'), array($userId));

        $objIds = array();

        while ($row = $ilDB->fetchAssoc($res)) {
            $objIds[] = (int) $row['obj_fi'];
        }

        return $objIds;
    }

    public function setSkillServiceEnabled($skillServiceEnabled)
    {
        $this->skillServiceEnabled = $skillServiceEnabled;
    }

    public function isSkillServiceEnabled(): bool
    {
        return $this->skillServiceEnabled;
    }

    public function getResultFilterTaxIds(): array
    {
        if ($this->getTestId() != -1) {
            return $this->getScoreSettings()->getResultDetailsSettings()->getTaxonomyFilterIds();
        }
        return [];
    }

    /**
     * Returns whether this test must consider skills, usually by providing
     * appropriate extensions in the user interface components. Skills must be
     * considered if skill management is globally enabled and this object has
     * the skill service enabled as well.
     *
     * @see #isSkillServiceEnabled()
     * @see #isSkillManagementGloballyActivated()
     *
     * @return boolean whether this test takes skills into account.
     */
    public function isSkillServiceToBeConsidered(): bool
    {
        if (!$this->isSkillServiceEnabled()) {
            return false;
        }

        if (!self::isSkillManagementGloballyActivated()) {
            return false;
        }

        return true;
    }

    private static $isSkillManagementGloballyActivated = null;

    public static function isSkillManagementGloballyActivated(): ?bool
    {
        if (self::$isSkillManagementGloballyActivated === null) {
            $skmgSet = new ilSkillManagementSettings();

            self::$isSkillManagementGloballyActivated = $skmgSet->isActivated();
        }

        return self::$isSkillManagementGloballyActivated;
    }

    public function setShowGradingStatusEnabled($showGradingStatusEnabled)
    {
        $this->showGradingStatusEnabled = $showGradingStatusEnabled;
    }

    public function isShowGradingStatusEnabled(): bool
    {
        return $this->showGradingStatusEnabled;
    }

    public function setShowGradingMarkEnabled($showGradingMarkEnabled)
    {
        $this->showGradingMarkEnabled = $showGradingMarkEnabled;
    }


    public function isShowGradingMarkEnabled(): bool
    {
        return $this->showGradingMarkEnabled;
    }

    public function setFollowupQuestionAnswerFixationEnabled($followupQuestionAnswerFixationEnabled)
    {
        $this->followupQuestionAnswerFixationEnabled = $followupQuestionAnswerFixationEnabled;
    }

    public function isFollowupQuestionAnswerFixationEnabled(): bool
    {
        return $this->followupQuestionAnswerFixationEnabled;
    }

    public function setInstantFeedbackAnswerFixationEnabled($instantFeedbackAnswerFixationEnabled)
    {
        $this->instantFeedbackAnswerFixationEnabled = $instantFeedbackAnswerFixationEnabled;
    }

    public function isInstantFeedbackAnswerFixationEnabled(): bool
    {
        return $this->instantFeedbackAnswerFixationEnabled;
    }

    public function isForceInstantFeedbackEnabled(): ?bool
    {
        return $this->forceInstantFeedbackEnabled;
    }

    /**
     * @param boolean $forceInstantFeedbackEnabled
     */
    public function setForceInstantFeedbackEnabled($forceInstantFeedbackEnabled): void
    {
        $this->forceInstantFeedbackEnabled = $forceInstantFeedbackEnabled;
    }

    public static function ensureParticipantsLastActivePassFinished($testObjId, $userId, $a_force_new_run = false): void
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $lng = $DIC['lng'];
        $component_repository = $DIC['component.repository'];

        /* @var ilObjTest $testOBJ */

        $testOBJ = ilObjectFactory::getInstanceByRefId($testObjId, false);

        $activeId = $testOBJ->getActiveIdOfUser($userId);

        $testSessionFactory = new ilTestSessionFactory($testOBJ);

        $testSequenceFactory = new ilTestSequenceFactory($ilDB, $lng, $component_repository, $testOBJ);

        $testSession = $testSessionFactory->getSession($activeId);
        $testSequence = $testSequenceFactory->getSequenceByActiveIdAndPass($activeId, $testSession->getPass());
        $testSequence->loadFromDb();

        // begin-patch lok changed smeyer
        if ($a_force_new_run) {
            if ($testSequence->hasSequence()) {
                $testSession->increasePass();
            }
            $testSession->setLastSequence(0);
            $testSession->saveToDb();
        }
        // end-patch lok
    }

    public static function isParticipantsLastPassActive($testRefId, $userId): bool
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $lng = $DIC['lng'];
        $component_repository = $DIC['component.repository'];

        /* @var ilObjTest $testOBJ */

        $testOBJ = ilObjectFactory::getInstanceByRefId($testRefId, false);


        $activeId = $testOBJ->getActiveIdOfUser($userId);

        $testSessionFactory = new ilTestSessionFactory($testOBJ);
        // Added temporarily bugfix smeyer
        $testSessionFactory->reset();

        $testSequenceFactory = new ilTestSequenceFactory($ilDB, $lng, $component_repository, $testOBJ);

        $testSession = $testSessionFactory->getSession($activeId);
        $testSequence = $testSequenceFactory->getSequenceByActiveIdAndPass($activeId, $testSession->getPass());
        $testSequence->loadFromDb();

        return $testSequence->hasSequence();
    }

    /**
     * @return boolean
     */
    public function isTestFinalBroken(): bool
    {
        return $this->testFinalBroken;
    }

    /**
     * @param boolean $testFinalBroken
     */
    public function setTestFinalBroken($testFinalBroken)
    {
        $this->testFinalBroken = $testFinalBroken;
    }

    public function adjustTestSequence()
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $query = "
			SELECT COUNT(test_question_id) cnt
			FROM tst_test_question
			WHERE test_fi = %s
			ORDER BY sequence
		";

        $questRes = $ilDB->queryF($query, array('integer'), array($this->getTestId()));

        $row = $ilDB->fetchAssoc($questRes);
        $questCount = $row['cnt'];

        if ($this->getShuffleQuestions()) {
            $query = "
				SELECT tseq.*
				FROM tst_active tac
				INNER JOIN tst_sequence tseq
					ON tseq.active_fi = tac.active_id
				WHERE tac.test_fi = %s
			";

            $partRes = $ilDB->queryF(
                $query,
                array('integer'),
                array($this->getTestId())
            );

            while ($row = $ilDB->fetchAssoc($partRes)) {
                $sequence = @unserialize($row['sequence']);

                if (!$sequence) {
                    $sequence = array();
                }

                $sequence = array_filter($sequence, function ($value) use ($questCount) {
                    return $value <= $questCount;
                });

                $num_seq = count($sequence);
                if ($questCount > $num_seq) {
                    $diff = $questCount - $num_seq;
                    for ($i = 1; $i <= $diff; $i++) {
                        $sequence[$num_seq + $i - 1] = $num_seq + $i;
                    }
                }

                $new_sequence = serialize($sequence);

                $ilDB->update('tst_sequence', array(
                    'sequence' => array('clob', $new_sequence)
                ), array(
                    'active_fi' => array('integer', $row['active_fi']),
                    'pass' => array('integer', $row['pass'])
                ));
            }
        } else {
            $new_sequence = serialize($questCount > 0 ? range(1, $questCount) : array());

            $query = "
				SELECT tseq.*
				FROM tst_active tac
				INNER JOIN tst_sequence tseq
					ON tseq.active_fi = tac.active_id
				WHERE tac.test_fi = %s
			";

            $part_rest = $ilDB->queryF(
                $query,
                array('integer'),
                array($this->getTestId())
            );

            while ($row = $ilDB->fetchAssoc($part_rest)) {
                $ilDB->update('tst_sequence', array(
                    'sequence' => array('clob', $new_sequence)
                ), array(
                    'active_fi' => array('integer', $row['active_fi']),
                    'pass' => array('integer', $row['pass'])
                ));
            }
        }
    }

    /**
     * @return ilHtmlPurifierInterface|ilAssHtmlUserSolutionPurifier
     */
    protected function getHtmlQuestionContentPurifier(): ilHtmlPurifierInterface
    {
        return ilHtmlPurifierFactory::getInstanceByType('qpl_usersolution');
    }

    public function getScoreSettings(): ilObjTestScoreSettings
    {
        if (!$this->score_settings) {
            $this->score_settings = $this->getScoreSettingsRepository()
                ->getFor($this->getTestId());
        }
        return $this->score_settings;
    }

    public function getScoreSettingsRepository(): ScoreSettingsRepository
    {
        if (!$this->score_settings_repo) {
            $this->score_settings_repo = new ilObjTestScoreSettingsDatabaseRepository($this->db);
        }
        return $this->score_settings_repo;
    }
}
