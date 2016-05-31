<?php
namespace Inane\Observer;

/** 
 * @author philip
 * 
 */
abstract class AbstractObserver {
	abstract function update(AbstractSubject $subject_in);
}
