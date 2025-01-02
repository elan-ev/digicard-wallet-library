<?php

/**
 * WalletUser.php - User data model for Digital Student ID
 *
 * This file is part of the Digicard Wallet Library.
 *
 * @package    DigicardWalletLibrary
 * @author     Till Glöggler <gloeggler@elan-ev.de>
 * @author     Farbod Zamani <zamani@elan-ev.de>
 * @copyright  2025 ELAN e.V.
 * @license    https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace DigicardWalletLibrary;

/**
 * Class User
 *
 * This class represents a user (student) with their associated information.
 */
class WalletUser
{
    /** @var string */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $matrikel;

    /** @var string */
    private $imageUrl;

    /** @var string */
    private $institution;

    /** @var array */
    private $studycourses;

    /** @var string */
    private $semesterStart;

    /** @var string */
    private $semesterEnd;

    /**
     * User constructor.
     *
     * @param array $userData An associative array containing user data
     */
    public function __construct(array $userData)
    {
        $this->id = $userData['id'] ?? '';
        $this->name = $userData['name'] ?? '';
        $this->matrikel = $userData['matrikel'] ?? '';
        $this->imageUrl = $userData['imageUrl'] ?? '';
        $this->institution = $userData['institution'] ?? '';
        $this->studycourses = $userData['studycourses'] ?? [];
        $this->semesterStart = $userData['semesterStart'] ?? '';
        $this->semesterEnd = $userData['semesterEnd'] ?? '';
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getMatrikel(): string
    {
        return $this->matrikel;
    }

    /**
     * @param string $matrikel
     */
    public function setMatrikel(string $matrikel): void
    {
        $this->matrikel = $matrikel;
    }

    /**
     * @return string
     */
    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    /**
     * @param string $imageUrl
     */
    public function setImageUrl(string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    /**
     * @return string
     */
    public function getInstitution(): string
    {
        return $this->institution;
    }

    /**
     * @param string $institution
     */
    public function setInstitution(string $institution): void
    {
        $this->institution = $institution;
    }

    /**
     * @return array
     */
    public function getStudycourses(): array
    {
        return $this->studycourses;
    }

    /**
     * @param array $studycourses
     */
    public function setStudycourses(array $studycourses): void
    {
        $this->studycourses = $studycourses;
    }



    /**
     * @return string
     */
    public function getSemesterStart(): string
    {
        return $this->semesterStart;
    }

    /**
     * @param string $semesterStart
     */
    public function setSemesterStart(string $semesterStart): void
    {
        $this->semesterStart = $semesterStart;
    }

    /**
     * @return string
     */
    public function getSemesterEnd(): string
    {
        return $this->semesterEnd;
    }

    /**
     * @param string $semesterEnd
     */
    public function setSemesterEnd(string $semesterEnd): void
    {
        $this->semesterEnd = $semesterEnd;
    }
}
