<?php
	use Nette\Latte\MacroNode;
	use Nette\Latte\PhpWriter;
	use Latte\Macros\MacroSet;
	use Latte\Compiler;
	
	class Macros extends MacroSet {
		public static function install (Compiler $compiler) {
			$set = new static($compiler);
			
			$set->addMacro('ifCurrentIn', 'if (in_array(trim("$presenter->moduleName:$presenter->presenterName:$presenter->action $presenter->urlID"), $presenter->formatCurrent(%node.array))):', 'endif');
			
			$set->addMacro('dlink', function ($node, $writer) {
				$node->modifiers = preg_replace('#\|safeurl\s*(?=\||\z)#i', '', $node->modifiers);

				return $writer->write('echo %escape(%modify(Macros::defaultLink($_presenter, %node.word, %node.array?)))');
			}, null);
		}

		public static function defaultLink ($presenter, $destination, $parameters=array()) {
			$persistents = $presenter->getPersistentParams();
			$array['eshop'] = null;
		
			foreach ($persistents as $key => $value)
			{
				if ($presenter->$value && $value != 'eshop') {
					$array[$value] = NULL;
				}
			}
		
			return $presenter->link($destination, array_merge($array,$parameters));
		}
	}