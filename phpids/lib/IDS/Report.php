<?php

class IDS_Report implements Countable, IteratorAggregate
{

    /**
     * Event container
     *
     * @var array
     */
    protected $events = array();

    /**
     * List of affected tags
     *
     * This list of tags is collected from the collected event objects on
     * demand when IDS_Report->getTags() is called
     *
     * @var    array
     */
    protected $tags = array();

    /**
     * Impact level
     *
     * The impact level is calculated on demand by adding the results of the
     * event objects on IDS_Report->getImpact()
     *
     * @var integer
     */
    protected $impact = 0;

    /**
     * Centrifuge data
     *
     * This variable - initiated as an empty array - carries all information
     * about the centrifuge data if available
     *
     * @var array
     */
    protected $centrifuge = array();

    /**
     * Constructor
     *
     * @param array $events the events the report should include
     *
     * @return void
     */
    public function __construct(array $events = null)
    {
        if ($events) {
            foreach ($events as $event) {
                $this->addEvent($event);
            }
        }
    }

    /**
     * Adds an IDS_Event object to the report
     *
     * @param object $event IDS_Event
     *
     * @return object $this
     */
    public function addEvent(IDS_Event $event)
    {
        $this->clear();
        $this->events[$event->getName()] = $event;

        return $this;
    }

    /**
     * Get event by name
     *
     * In most cases an event is identified by the key of the variable that
     * contained maliciously appearing content
     *
     * @param scalar $name the event name
     *
     * @throws InvalidArgumentException if argument is invalid
     * @return mixed IDS_Event object or false if the event does not exist
     */
    public function getEvent($name)
    {
        if (!is_scalar($name)) {
            throw new InvalidArgumentException(
                'Invalid argument type given'
            );
        }

        if ($this->hasEvent($name)) {
            return $this->events[$name];
        }

        return false;
    }

    /**
     * Returns list of affected tags
     *
     * @return array
     */
    public function getTags()
    {
        if (!$this->tags) {
            $this->tags = array();

            foreach ($this->events as $event) {
                $this->tags = array_merge($this->tags,
                                          $event->getTags());
            }

            $this->tags = array_values(array_unique($this->tags));
        }

        return $this->tags;
    }

    /**
     * Returns total impact
     *
     * Each stored IDS_Event object and its IDS_Filter sub-object are called
     * to calculate the overall impact level of this request
     *
     * @return integer
     */
    public function getImpact()
    {
        if (!$this->impact) {
            $this->impact = 0;
            foreach ($this->events as $event) {
                $this->impact += $event->getImpact();
            }
        }

        return $this->impact;
    }

    /**
     * Checks if a specific event with given name exists
     *
     * @param scalar $name the event name
     *
     * @throws InvalidArgumentException if argument is illegal
     *
     * @return boolean
     */
    public function hasEvent($name)
    {
        if (!is_scalar($name)) {
            throw new InvalidArgumentException('Invalid argument given');
        }

        return isset($this->events[$name]);
    }

    /**
     * Returns total amount of events
     *
     * @return integer
     */
    public function count()
    {
        return count($this->events);
    }

     /**
     * Return iterator object
     *
     * In order to provide the possibility to directly iterate over the
     * IDS_Event object the IteratorAggregate is implemented. One can easily
     * use foreach() to iterate through all stored IDS_Event objects.
     *
     * @return ArrayObject the event collection
     */
    public function getIterator()
    {
        return new ArrayObject($this->events);
    }

    /**
     * Checks if any events are registered
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->events);
    }

    /**
     * Clears calculated/collected values
     *
     * @return void
     */
    protected function clear()
    {
        $this->impact = 0;
        $this->tags   = array();
    }

    /**
     * This method returns the centrifuge property or null if not
     * filled with data
     *
     * @return array/null
     */
    public function getCentrifuge()
    {
        return ($this->centrifuge && count($this->centrifuge) > 0)
            ? $this->centrifuge : null;
    }

    /**
     * This method sets the centrifuge property
     *
     * @param array $centrifuge the centrifuge data
     *
     * @throws InvalidArgumentException if argument is illegal
     *
     * @return boolean true is arguments were valid
     */
    public function setCentrifuge($centrifuge = array())
    {
        if (is_array($centrifuge) && $centrifuge) {
            $this->centrifuge = $centrifuge;
            return true;
        }
        throw new InvalidArgumentException('Invalid argument given');
    }

    /**
     * Directly outputs all available information
     *
     * @return string
     */
    public function __toString()
    {
        if (!$this->isEmpty()) {
            $output  = '';
            $output .= 'Total impact: ' . $this->getImpact() . "<br/>\n";
            $output .= 'Affected tags: ' . join(', ', $this->getTags()) .
                "<br/>\n";

            foreach ($this->events as $event) {
                $output .= "<br/>\nVariable: " .
                    htmlspecialchars($event->getName()) . ' | Value: ' .
                    htmlspecialchars($event->getValue()) . "<br/>\n";
                $output .= 'Impact: ' . $event->getImpact() . ' | Tags: ' .
                    join(', ', $event->getTags()) . "<br/>\n";

                foreach ($event as $filter) {
                    $output .= 'Description: ' . $filter->getDescription() .
                        ' | ';
                    $output .= 'Tags: ' . join(', ', $filter->getTags()) .
                        ' | ';
                    $output .= 'ID: ' . $filter->getId() .
                        "<br/>\n";
                }
            }

            $output .= '<br/>';

            if ($centrifuge = $this->getCentrifuge()) {
                $output .= 'Centrifuge detection data';
                $output .= '<br/>  Threshold: ' . 
                    ((isset($centrifuge['threshold'])&&$centrifuge['threshold']) ?
                    $centrifuge['threshold'] : '---');
                $output .= '<br/>  Ratio: ' . 
                    ((isset($centrifuge['ratio'])&&$centrifuge['ratio']) ?
                    $centrifuge['ratio'] : '---');
                if(isset($centrifuge['converted'])) {
                    $output .= '<br/>  Converted: ' . $centrifuge['converted'];
                }
                $output .= "<br/><br/>\n";
            }
        }

        return isset($output) ? $output : '';
    }
}
