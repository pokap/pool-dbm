<?php

namespace Pok\PoolDBM\Guesser;

/**
 * TransactionGuesserPass
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class TransactionGuesserPass extends AbstractGuesserPass
{
    /**
     * {@inheritdoc}
     */
    public function guess($name, $manager)
    {
        return method_exists('beginTransaction', $manager)
            && method_exists('commit', $manager)
            && method_exists('rollback', $manager);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'transaction';
    }
}
