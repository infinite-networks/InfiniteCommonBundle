<?php

/**
 * This file is part of the Watchlister project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Abstract implementation of the VoterInterface to reduce code duplication. Supports
 * a voter that checks a single attribute and will automatically handle calling
 * supportsClass() and supportsAttribute for each attribute being asked of it.
 */
abstract class BaseVoter implements VoterInterface
{
    protected $attribute;

    public function supportsAttribute($attribute)
    {
        return $this->attribute === $attribute;
    }

    /**
     * Called for a supported attribute for the voter to make a decision.
     *
     * @param TokenInterface $token
     * @param mixed $object
     * @param string $attribute
     * @return mixed
     */
    abstract protected function doAttributeVote(TokenInterface $token, $object, $attribute);

    /**
     * Handles most of the repeatable voter logic and calls doAttributeVote when it finds
     * an attribute that the implementing voter supports.
     *
     * @param TokenInterface $token
     * @param mixed $object
     * @param array $attributes
     * @return int
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$this->supportsClass($object)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $result = $this->doAttributeVote($token, $object, $attribute);
            if ($result) {
                return $result;
            }
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }
}
