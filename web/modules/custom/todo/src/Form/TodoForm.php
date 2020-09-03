<?php

namespace Drupal\todo\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide a TODO List.
 */
class TodoForm extends FormBase {

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Form Builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new form object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Entity Type Manager service.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The Form Builder service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, FormBuilderInterface $formBuilder) {
    $this->entityTypeManager = $entityTypeManager;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'todo_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $tasksResult = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('uid', $this->currentUser()->id())
      ->execute();

    $tasks = $tasksResult ? Node::loadMultiple($tasksResult) : [];

    $form['tasks'] = [
      '#type' => 'table',
      '#header' => [$this->t('Task'), $this->t('Complete')],
      '#empty' => t('There are no tasks yet.'),
    ];

    /** @var \Drupal\node\Entity\Node $task */
    foreach ($tasks as $task) {
      $form['tasks']['todo-' . $task->id()] = [
        'title' => [
            '#type' => 'checkbox',
           '#options' => $task->getTitle(),
          '#title' => $task->getTitle(),
          '#default_value' => FALSE,
          //'#markup' => $task->getTitle(),
        ],
        'action' => [
          '#task_id' => $task->id(),
          '#type' => 'button',
          '#name' => 'todo-' . $task->id(),
          // '#value' => $this->t('Mark Complete'),
          '#ajax' => [
            'callback' => '::markComplete',
            'event' => 'click',
            'wrapper' => 'todo-' . $task->id(),
          ],
        ],
        '#id' => 'todo-' . $task->id(),
      ];
    }


    $form['add'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add Item'),
    ];
    $form['add']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#placeholder' => $this->t('E.g. Adopt an owl'),
    ];

    $form['add']['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Add'),
      '#ajax' => [
        'callback' => '::addTask',
        'event' => 'click',
      ],
    ];
    $form['#theme'] = 'todo_template';
    
    return $form;
  }

  /**
   * AJAX Callback to remove a task.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|string
   *   The Ajax Response commands.
   */
  public function markComplete(array &$form, FormStateInterface $form_state) {

    $trigger = $form_state->getTriggeringElement();
    $nid = $trigger['#task_id'];
    $task = Node::load($nid);

    // Don't allow removing other user's items.
    if ($task->getOwnerId() !== $this->currentUser()->id()) {
      return '';
    }

    $response = new AjaxResponse();
    try {
      $task->delete();
      $response->addCommand(new RemoveCommand('[data-drupal-selector="todo-' . $nid . '"]'));
      $response->addCommand(new MessageCommand($this->t('Item updated')));
    }
    catch (\Exception $e) {
      $response->addCommand(new MessageCommand($this->t('Unable to update item')));
    }

    return $response;
  }

  /**
   * AJAX Callback to add a new task.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|string
   *   The Ajax Response commands.
   */
  public function addTask(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    try {
      Node::create([
        'type' => 'todo',
        'uid' => $this->currentUser()->id(),
        'title' => $form_state->getValue('title'),
      ])->save();

      $form = $this->formBuilder->rebuildForm($this->getFormId(), $form_state, $form);
      $settings = [];
      foreach (Element::children($form['tasks']) as $taskRow) {
        $callbackElement = RenderElement::preRenderAjaxForm($form['tasks'][$taskRow]['action']);
        $settings = array_merge($settings, $callbackElement['#attached']['drupalSettings']['ajax']);
      }
      $response->addCommand(new SettingsCommand(['ajax' => $settings], TRUE));
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="edit-tasks"]', $form['tasks']));
      $response->addCommand(new MessageCommand($this->t('Task added')));
    }
    catch (\Exception $e) {
      $response->addCommand(new MessageCommand($this->t('Unable to add task')));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method as fallback if AJAX is disabled.
  }

}
