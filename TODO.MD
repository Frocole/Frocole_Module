----
TODO

        Translation new (Segment) forms

        When deleting a Course (and it's teacher), other courses with that same teacher will fail to showup.
        So not delete teachers?
        
----

Database Modification due to Segments

1) Add 'Segments' table
    SegmentID, Primary int AUTO_INCREMENT
    SegmentName, varchar(20) utf8mb4_general_ci

2) Create one record in this table with an empty 'SegmentName' field for backwards compatibility.
   The 'SegmentID' can be used to map existing Courses, Users and InfoTexten onto this segment.

3) Add a 'SegmentID' int column to the 'Courses' table.
    Fill existing records with the SegmentID obtained in step 2.
4) Add a 'SegmentID' int column to the 'InfoTexten' table.
    Fill existing records with the SegmentID obtained in step 2.
5) Add a 'SegmentID' int column to the 'Users' table.
    Fill existing records with the SegmentID obtained in step 2.

----
Validation
----

* AddCourseForm.validateForm() needs some sanity checks
    (indicator label length)
    (empty elements)
    (problemetic characters)
    (correct issues as a trailing / before saving)

----
More Management UI
----

* Add UI for managing users (move people of of a group)?
* Make sure teacher selection scales well (be able to mark them as teacher (and default to false) ?)
* Add Remove User from Group link to Users table.
* Add password generation code (reset passwords).

----
* DONE Complete translation.
* DONE Implement validateForm() in AddCourse.php
* DONE Define Access on forms (restict to admin).
* DONE Change Students List into a table (id|name)
