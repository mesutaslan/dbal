<?php

/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Export\Driver;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * ClassMetadata exporter for PHP code
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class PhpExporter extends AbstractExporter
{
    protected $_extension = '.php';

    /**
     * Converts a single ClassMetadata instance to the exported format
     * and returns it
     *
     * @param ClassMetadataInfo $metadata 
     * @return mixed $exported
     */
    public function exportClassMetadata(ClassMetadataInfo $metadata)
    {
        $lines = array();
        $lines[] = '<?php';
        $lines[] = null;
        $lines[] = 'use Doctrine\ORM\Mapping\ClassMetadata;';
        $lines[] = null;
        $lines[] = "\$metadata = new ClassMetadata('" . $metadata->name . "');";

        if ($metadata->isMappedSuperclass) {
            $lines[] = '$metadata->isMappedSuperclass = true;';
        }

        if ($metadata->inheritanceType) {
            $lines[] = '$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_' . $this->_getInheritanceTypeString($metadata->inheritanceType) . ');';
        }

        if ($metadata->customRepositoryClassName) {
            $lines[] = "\$metadata->customRepositoryClassName = '" . $metadata->customRepositoryClassName . "';";
        }

        if ($metadata->primaryTable) {
            $lines[] = '$metadata->setPrimaryTable(' . $this->_varExport($metadata->primaryTable) . ');';
        }

        if ($metadata->discriminatorColumn) {
            $lines[] = '$metadata->setDiscriminatorColumn(' . $this->_varExport($metadata->discriminatorColumn) . ');';
        }

        if ($metadata->discriminatorMap) {
            $lines[] = '$metadata->setDiscriminatorMap(' . $this->_varExport($metadata->discriminatorMap) . ');';
        }

        if ($metadata->changeTrackingPolicy) {
            $lines[] = '$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_' . $this->_getChangeTrackingPolicyString($metadata->changeTrackingPolicy) . ');';
        }

        foreach ($metadata->fieldMappings as $fieldMapping) {
            $lines[] = '$metadata->mapField(' . $this->_varExport($fieldMapping) . ');';
        }

        if ($generatorType = $this->_getIdGeneratorTypeString($metadata->generatorType)) {
            $lines[] = '$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_' . $generatorType . ');';
        }

        foreach ($metadata->associationMappings as $associationMapping) {
            $associationMappingArray = array(
                'fieldName'    => $associationMapping->sourceFieldName,
                'targetEntity' => $associationMapping->targetEntityName,
                'cascade'     => array(
                    'remove'  => $associationMapping->isCascadeRemove,
                    'persist' => $associationMapping->isCascadePersist,
                    'refresh' => $associationMapping->isCascadeRefresh,
                    'merge'   => $associationMapping->isCascadeMerge,
                    'detach'  => $associationMapping->isCascadeDetach,
                ),
            );
            
            if ($associationMapping instanceof \Doctrine\ORM\Mapping\OneToOneMapping) {
                $method = 'mapOneToOne';
                $oneToOneMappingArray = array(
                    'mappedBy'      => $associationMapping->mappedByFieldName,
                    'joinColumns'   => $associationMapping->joinColumns,
                    'orphanRemoval' => $associationMapping->orphanRemoval,
                );
                
                $associationMappingArray = array_merge($associationMappingArray, $oneToOneMappingArray);
            } else if ($associationMapping instanceof \Doctrine\ORM\Mapping\OneToManyMapping) {
                $method = 'mapOneToMany';
                $oneToManyMappingArray = array(
                    'mappedBy'      => $associationMapping->mappedByFieldName,
                    'orphanRemoval' => $associationMapping->orphanRemoval,
                );
                
                $associationMappingArray = array_merge($associationMappingArray, $oneToManyMappingArray);
            } else if ($associationMapping instanceof \Doctrine\ORM\Mapping\ManyToManyMapping) {
                $method = 'mapManyToMany';
                $manyToManyMappingArray = array(
                    'mappedBy'  => $associationMapping->mappedByFieldName,
                    'joinTable' => $associationMapping->joinTable,
                );
                
                $associationMappingArray = array_merge($associationMappingArray, $manyToManyMappingArray);
            }
            $lines[] = '$metadata->' . $method . '(' . $this->_varExport($associationMappingArray) . ');';
        }

        return implode("\n", $lines);
    }

    protected function _varExport($var)
    {
        $export = var_export($var, true);
        $export = str_replace("\n", PHP_EOL . str_repeat(' ', 8), $export);
        $export = str_replace('  ', ' ', $export);
        $export = str_replace('array (', 'array(', $export);
        $export = str_replace('array( ', 'array(', $export);
        $export = str_replace(',)', ')', $export);
        $export = str_replace(', )', ')', $export);
        $export = str_replace('  ', ' ', $export);

        return $export;
    }
}