<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\generator\generators;

use Craft;
use craft\generator\BaseGenerator;
use craft\generator\NodeVisitor;
use craft\generator\Workspace;
use craft\helpers\ArrayHelper;
use Nette\PhpGenerator\PhpNamespace;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeTraverser;
use yii\base\Component;
use yii\helpers\Inflector;
use yii\web\Application;

/**
 * Creates a new service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Service extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;
    private string $componentId;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Service name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Service namespace:', [
            'default' => "$this->baseNamespace\\services",
        ]);

        $this->displayName = Inflector::camel2words($this->className);
        $this->componentId = lcfirst($this->className);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(Component::class);

        $class = $this->createClass($this->className, Component::class);
        $namespace->add($class);

        $class->setComment("$this->displayName service");

        $this->writePhpClass($namespace);

        $message = "**Service created!**";
        if (
            !$this->module instanceof Application &&
            !$this->modifyModuleFile(function(Workspace $workspace) {
                $serviceClassName = $workspace->importClass("$this->namespace\\$this->className");

                if (!$workspace->modifyCode(new NodeVisitor(
                    enterNode: function(Node $node) use ($workspace, $serviceClassName) {
                        if (!$workspace->isMethod($node, 'config')) {
                            return null;
                        }
                        // Make sure an array is returned
                        /** @var ClassMethod $node */
                        /** @var Return_|null $returnStmt */
                        $returnStmt = ArrayHelper::firstWhere($node->stmts, fn(Stmt $stmt) => $stmt instanceof Return_);
                        if (!$returnStmt || !$returnStmt->expr instanceof Array_) {
                            return NodeTraverser::STOP_TRAVERSAL;
                        }
                        $workspace->mergeIntoArray($returnStmt->expr, [
                            'components' => [
                                $this->componentId => new ClassConstFetch(new Name($serviceClassName), 'class'),
                            ],
                        ]);
                        return NodeTraverser::STOP_TRAVERSAL;
                    }
                ))) {
                    return false;
                }

                $workspace->appendDocCommentOnClass("@property-read $serviceClassName \$$this->componentId");

                return true;
            })
        ) {
            $moduleFile = $this->moduleFile();
            $message .= "\n" . <<<MD
Add the following code to `$moduleFile` to register the service:

```
use $this->namespace\\$this->className;

public static function config(): array
{
    return [
        'components' => [
            '$this->componentId' => $this->className::class,
        ],
    ];
}
```

You should also add a `@property-read` tag to the class’s DocBlock comment, to help with IDE autocompletion:

```
/**
 * @property-read $this->className \$$this->componentId
 */
```
MD;
        }

        $this->command->success($message);
        return true;
    }
}
