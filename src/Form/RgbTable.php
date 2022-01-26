<?php

namespace Drupal\rgb\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class that create table form.
 */
class RgbTable extends FormBase {

  /**
   * Table headers.
   *
   * @var string[]
   */
  protected $headlines;

  /**
   * Inactive cells.
   *
   * @var string[]
   */
  protected $calculatedCells;

  /**
   * Primordial number of tables.
   *
   * @var int
   */
  protected $tables = 1;

  /**
   * Primordial number of rows.
   *
   * @var int
   */
  protected $rows = 1;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): RgbTable {
    $instance = parent::create($container);
    $instance->setMessenger($container->get('messenger'));
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'rgbTable';
  }

  /**
   * A function that contain header of table and inactive cells.
   */
  public function buildHeadlines(): void {
    $this->headlines = [
      'year' => $this->t("Year"),
      'jan' => $this->t("Jan"),
      'feb' => $this->t("Feb"),
      'mar' => $this->t("Mar"),
      'q1' => $this->t("Q1"),
      'apr' => $this->t("Apr"),
      'may' => $this->t("May"),
      'jun' => $this->t("Jun"),
      'q2' => $this->t("Q2"),
      'jul' => $this->t("Jul"),
      'aug' => $this->t("Aug"),
      'sep' => $this->t("Sep"),
      'q3' => $this->t("Q3"),
      'oct' => $this->t("Oct"),
      'nov' => $this->t("Nov"),
      'dec' => $this->t("Dec"),
      'q4' => $this->t("Q4"),
      'ytd' => $this->t("YTD"),
    ];
    $this->calculatedCells = [
      'year' => $this->t("Year"),
      'q1' => $this->t("Q1"),
      'q2' => $this->t("Q2"),
      'q3' => $this->t("Q3"),
      'q4' => $this->t("Q4"),
      'ytd' => $this->t("YTD"),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#prefix'] = '<div id="rgb-table">';
    $form['#suffix'] = '</div>';
    // Button for adding new tables.
    $form['addTable'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add table'),
      '#submit' => [
        '::addTable',
      ],
      '#ajax' => [
        'callback' => '::submitAjax',
        'wrapper' => 'rgb-table',
      ],
    ];
    // Button for adding new rows.
    $form['addRow'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add year'),
      '#submit' => [
        '::addRow',
      ],
      '#ajax' => [
        'callback' => '::submitAjax',
        'wrapper' => 'rgb-table',
      ],
    ];
    // Submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'wrapper' => 'rgb-table',
      ],
    ];
    $this->buildHeadlines();
    // Call the function that creates tables.
    $this->buildTables($form, $form_state);
    $form['#attached']['library'][] = 'rgb/rgb_css';
    return $form;
  }

  /**
   * Function for building tables.
   */
  public function buildTables(array &$form, FormStateInterface $form_state) {
    // Cycle that contain number of tables we have to build.
    for ($i = 0; $i < $this->tables; $i++) {
      $form[$i] = [
        '#type' => 'table',
        '#header' => $this->headlines,
        '#tree' => 'TRUE',
      ];
      $this->buildRows($i, $form[$i], $form_state);
    }
  }

  /**
   * Function for building rows.
   */
  public function buildRows($table_id, array &$table, FormStateInterface $form_state): void {
    // Cycle that contain number of tables we have to build.
    for ($i = $this->rows; $i > 0; $i--) {
      foreach ($this->headlines as $key => $value) {
        $table[$i][$key] = [
          '#type' => 'number',
          '#step' => '0.01',
        ];
        // Condition for providing settings of inactive cells.
        if (array_key_exists($key, $this->calculatedCells)) {
          $value = $form_state->getValue($table_id . '][' . $i . '][' . $key, 0);
          $table[$i][$key]['#disabled'] = TRUE;
          $table[$i][$key]['#default_value'] = round($value, 2);
        }
        $table[$i]['year']['#default_value'] = date('Y') - $i;
      }
    }
  }

  /**
   * Function for increasing the number of rows.
   */
  public function addRow(array &$form, FormStateInterface $form_state): array {
    $this->rows++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Function for increasing the number of tables.
   */
  public function addTable(array &$form, FormStateInterface $form_state): array {
    $this->tables++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Refreshing the page.
   */
  public function submitAjax(array $form, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get values of form.
    $values = $form_state->getValues();
    foreach ($values as $tableKey => $table) {
      foreach ($table as $rowKey => $row) {

        $path = $tableKey . '][' . $rowKey . '][';
        // Calculate quarter and year values.
        $q1 = ($row['jan'] + $row['feb'] + $row['mar'] + 1) / 3;
        $q2 = ($row['apr'] + $row['may'] + $row['jun'] + 1) / 3;
        $q3 = ($row['jul'] + $row['aug'] + $row['sep'] + 1) / 3;
        $q4 = ($row['oct'] + $row['nov'] + $row['dec'] + 1) / 3;
        $ytd = ($q1 + $q2 + $q3 + $q4 + 1) / 4;
        // Set our values to form.
        $form_state->setValue($path . 'q1', $q1);
        $form_state->setValue($path . 'q2', $q2);
        $form_state->setValue($path . 'q3', $q3);
        $form_state->setValue($path . 'q4', $q4);
        $form_state->setValue($path . 'ytd', $ytd);
      }
    }
    $this->messenger->addStatus('Form is valid!');
    $form_state->setRebuild();
  }

  /**
   * Update from associative array to normal.
   */
  public function updatedArray($array): array {
    $values = [];
    for ($i = $this->rows; $i > 0; $i--) {
      foreach ($array[$i] as $key => $value) {
        if (!array_key_exists($key, $this->calculatedCells)) {
          if ($value == "") {
            $value = 0;
          }
          $values[] = $value;
        }
      }
    }
    return $values;
  }

  /**
   * Validation of table form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Getting values.
    $data = $form_state->getValues();
    // Array for all tables values.
    $cell_position = [];
    for ($i = 0; $i < $this->tables; $i++) {
      // Update array.
      $values = $this->updatedArray($data[$i]);
      // Calculate number of active cells.
      $months = $this->rows * 12;
      // Variable for position of filled cells.
      $position = [];
      // Count of filled cells.
      $nonEmpty = 0;
      // Cycle for getting positions of filled cells.
      for ($q = 0; $q < $months; $q++) {
        if ($values[$q] !== 0) {
          $position[] = $q;
          $nonEmpty++;
        }
      }
      // Cycle for comparison of position.
      for ($k = 0; $k < $nonEmpty - 1; $k++) {
        // Check that there are not gaps between two values.
        $difference = $position[$k + 1] - $position[$k];
        if ($difference != 1) {
          $form_state->setErrorByName($k, 'Gap');
        }
      }
      $cell_position[] = $position;
    }
    // Cycle for checking similarity of tables.
    for ($i = 0; $i < $this->tables - 1; $i++) {
      if ($cell_position[$i] != $cell_position[$i + 1]) {
        $form_state->setErrorByName($i, 'Tables are not the same!');
      }
    }
  }

}
