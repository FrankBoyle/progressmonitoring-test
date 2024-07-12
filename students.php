<?php
session_start();
include('./users/auth_session.php');
include('./users/db.php');

// Ensure account_id is in session
$account_id = $_SESSION['account_id'];
$school_id = $_SESSION['school_id'];

// Fetch the schools associated with the logged-in user
$query = $connection->prepare("SELECT s.school_id, s.SchoolName FROM Schools s JOIN Teachers t ON s.school_id = t.school_id WHERE t.account_id = :account_id");
$query->bindParam("account_id", $account_id, PDO::PARAM_INT);
$query->execute();
$schools = $query->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-LKFCCN4XXS"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-LKFCCN4XXS');
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Managment</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">

</head>
<body>

<div class="dashboard">
    <header class="dashboard-header">
        <div class="logo">
            <img src="bFactor_logo.png" alt="Logo">
        </div>
        <div class="header-icons">
            <div class="school-selector">
                <label for="school-select">Select School:</label>
                <select id="school-select">
                    <?php foreach ($schools as $school): ?>
                        <option value="<?= htmlspecialchars($school['school_id']) ?>" <?= $school['school_id'] == $_SESSION['school_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($school['SchoolName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <a href="manage.php" class="nav-link">
                    <button class="btn btn-primary">Manage</button>
                </a>
            <?php endif; ?>
            <a href="students.php" class="nav-link">
                <i class="nav-icon"></i>
                <p>Home</p>
            </a>
            <a href="./users/logout.php" class="nav-link">
                <i class="nav-icon"></i>
                <p>Sign Out</p>
            </a>
        </div>
    </header>

    <main class="content-students">
    <input type="hidden" id="selected-student-id" value="">

        <section class="box create-group">
            <h2>Groups <button class="add-group-btn" onclick="showAddGroupModal()">+</button></h2>
            <div id="group-list">
                <ul>
                    <?php foreach ($groups as $group): ?>
                        <li data-group-id="<?= htmlspecialchars($group['group_id']) ?>" data-group-name="<?= htmlspecialchars($group['group_name']) ?>">
                            <?= htmlspecialchars($group['group_name']) ?>
                            <button class="options-btn" onclick="showGroupOptions(event, '<?= htmlspecialchars($group['group_id']) ?>', '<?= htmlspecialchars(addslashes($group['group_name'])) ?>')">Options</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <section class="box students-list">
            <h2>Students <button class="add-student-btn">+</button></h2>
            <div class="message" id="students-message">Please use groups to see students.</div>
            <ul id="student-list" style="display: none;">
                <?php foreach ($allStudents as $student): ?>
                    <li data-student-id="<?= htmlspecialchars($student['student_id']) ?>"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <!-- Add the new Edit Column Names button in the goal list section -->
        <section class="box existing-groups">
            <h2>Goals <button class="add-goal-btn" onclick="showAddGoalModal()">+</button></h2>
            <div class="message" id="goals-message">Click a student to see their goals.</div>
            <div id="goal-list" style="display: none;">
                <!-- Goals will be loaded here and grouped by metadata_id -->
            </div>
        </section>
    </main>
</div>

<!-- Add Group Modal -->
<div id="add-group-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="hideAddGroupModal()">&times;</span>
        <h2>Add New Group</h2>
        <form id="add-group-form" onsubmit="addGroup(event)">
            <label for="group-name">Group Name:</label>
            <input type="text" id="group-name" name="group_name" required>
            <button type="submit">Add Group</button>
        </form>
    </div>
</div>

   <!-- Place the Edit Group button here, outside the modal -->
   <!-- <button class="edit-group-btn" onclick="showEditGroupModal()">Edit Group</button>-->

<!-- Add Student Modal -->
<div id="add-student-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="hideAddStudentModal()">&times;</span>
        <h2>Assign Students to Group</h2>
        <div style="margin-top: 20px;">
            <form id="assign-students-form" onsubmit="assignStudentsToGroup(event)">
                <div style="display: flex; align-items: center;">
                    <div style="margin-right: 10px;">
                        <select name="student_id" class="select2" style="width: 200px;" data-placeholder="Student name here" multiple>
                            <option></option>
                            <!-- Options will be dynamically populated -->
                        </select>
                    </div>
                    <button type="submit" name="assign_to_group">Assign to Group</button>
                </div>
            </form>
        </div>
        <h2>Remove Students from Group</h2>
        <div id="group-students-list-add">
            <!-- Students will be loaded here dynamically -->
        </div>
        <h2>Add New Student</h2>
        <form id="add-student-form" onsubmit="addStudent(event)">
            <div class="form-group">
                <label for="first-name">First Name:</label>
                <input type="text" id="first-name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last-name">Last Name:</label>
                <input type="text" id="last-name" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="date-of-birth">Date of Birth:</label>
                <input type="date" id="date-of-birth" name="date_of_birth" required>
            </div>
            <div class="form-group">
                <label for="grade-level">Grade Level:</label>
                <input type="text" id="grade-level" name="grade_level" required>
            </div>
            <button type="submit">Add Student</button>
        </form>
    </div>
</div>

<!-- Add Goal Modal -->
<div id="add-goal-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="hideAddGoalModal()">&times;</span>
        <h2>Add New Goal</h2>
        <form id="add-goal-form" onsubmit="addGoal(event)">
            <div class="selector-area">
                <div id="columnSelectorTitle" class="selector-title">Goal Category Options:</div>
                <div id="metadataOptionSelector" class="checkbox-container">
                    <div class="selector-item" data-option="template">Category Template</div>
                    <div class="selector-item" data-option="existing">Previously Used Category</div>
                </div>
            </div>

            <div id="templateDropdown" class="form-group" style="display: none;">
                <label for="template-metadata-select">Select Category Template:</label>
                <select id="template-metadata-select" name="template_id" onchange="showColumnNames('template')">
                    <option value="" disabled selected>Select a category to see column options</option>
                </select>
            </div>

            <div id="existingDropdown" class="form-group" style="display: none;">
                <label for="existing-metadata-select">Select Existing Category:</label>
                <select id="existing-metadata-select" name="existing_category_id" onchange="showColumnNames('existing')">
                    <option value="" disabled selected>Select a category to see column options</option>
                </select>
            </div>

            <div id="columnNamesDisplay" style="display: none; margin-top: 10px;">
                <h2>Column Names:</h2>
                <ul id="columnNamesList"></ul>
            </div>

            <div class="form-group">
                <label for="goal-description">Goal Description:</label>
                <textarea id="goal-description" name="goal_description" required></textarea>
            </div>
            <div class="form-group">
                <label for="goal-date">Goal Date:</label>
                <input type="date" id="goal-date" name="goal_date" required>
            </div>
            <button type="submit">Add Goal</button>
        </form>
    </div>
</div>

<!-- Group Options -->
<div id="group-options" class="group-options">
    <button onclick="editGroup()">Edit Group</button>
</div>

<!-- Edit Group Modal -->
<div id="edit-group-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="hideEditGroupModal()">&times;</span>
        <h2>Edit Group</h2>
        <form id="edit-group-form" onsubmit="updateGroup(event)">
            <input type="hidden" id="edit-group-id">
            <div class="form-group">
                <label for="edit-group-name">Group Name:</label>
                <input type="text" id="edit-group-name" name="group_name" required>
            </div>
            <button class="save-btn" type="submit">Save Changes</button>
        </form>
        <button class="delete-btn" onclick="deleteGroup()">Delete Group</button>
        <div id="group-students-list-edit">
            <!-- Students will be loaded here dynamically -->
        </div>
        <h2>Share Group</h2>
        <form id="share-group-form" onsubmit="shareGroup(event)">
            <input type="hidden" id="share-group-id">
            <div class="form-group">
                <select id="share-teacher-id" name="shared_teacher_id">
                    <option value="">Select staff here</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= htmlspecialchars($teacher['teacher_id']) ?>">
                            <?= htmlspecialchars($teacher['fname'] . ' ' . $teacher['lname']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Share</button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

<script>
let quillInstances = {}; // Initialize quillInstances globally

document.addEventListener('DOMContentLoaded', function() {
    loadGroups();
    loadStaff();
    loadTemplates();
    loadExistingCategories();
    lightbox.init();

    window.showAddGoalModal = showAddGoalModal;
    window.hideAddGoalModal = hideAddGoalModal;

    document.querySelector('.add-goal-btn').addEventListener('click', showAddGoalModal);
    document.querySelector('.add-group-btn').addEventListener('click', showAddGroupModal);

    window.hideAddGroupModal = hideAddGroupModal;
    window.hideAddStudentModal = hideAddStudentModal;

    document.addEventListener('click', function(event) {
        const optionsMenu = document.getElementById('group-options');
        if (optionsMenu && !optionsMenu.contains(event.target)) {
            optionsMenu.style.display = 'none';
        }
    });

    $('.select2').select2();

    const goalList = document.getElementById('goal-list');
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                populateStudentsAndGoals();
            }
        });
    });
    observer.observe(goalList, { childList: true, subtree: true });

    populateStudentsAndGoals();

    // Metadata Option Selector
    const metadataOptionSelector = document.getElementById('metadataOptionSelector');
    const templateDropdown = document.getElementById('templateDropdown');
    const existingDropdown = document.getElementById('existingDropdown');

    metadataOptionSelector.addEventListener('click', function(event) {
        if (event.target.classList.contains('selector-item')) {
            const items = metadataOptionSelector.querySelectorAll('.selector-item');
            items.forEach(item => item.classList.remove('selected'));
            event.target.classList.add('selected');

            const selectedOption = event.target.getAttribute('data-option');
            if (selectedOption === 'template') {
                templateDropdown.style.display = 'block';
                existingDropdown.style.display = 'none';
                document.getElementById('columnNamesDisplay').style.display = 'none';
            } else if (selectedOption === 'existing') {
                templateDropdown.style.display = 'none';
                existingDropdown.style.display = 'block';
                document.getElementById('columnNamesDisplay').style.display = 'none';
            }
        }
    });
    
    const schoolSelect = document.getElementById('school-select');
    if (schoolSelect) {
        schoolSelect.addEventListener('change', function() {
            const selectedSchoolId = this.value;
            //console.log('School selected:', selectedSchoolId); // Debugging statement
            fetch('./users/update_school_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `school_id=${encodeURIComponent(selectedSchoolId)}`
            })
            .then(response => {
                //console.log('Response status:', response.status); // Debugging statement
                return response.json();
            })
            .then(data => {
                //console.log('Response data:', data); // Debugging statement
                if (data.success) {
                    //console.log('Reloading page in 3 seconds'); // Debugging statement
                    setTimeout(function() {
                        location.reload(); // Reload the page to reflect the school change
                    }, 0); // 3 seconds delay
                } else {
                    console.error('Error updating school:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
});

document.querySelector('.add-student-btn').addEventListener('click', function() {
    const selectedGroup = document.querySelector('.selected-group');
    if (selectedGroup) {
        const groupId = selectedGroup.getAttribute('data-group-id');
        if (groupId) {
            loadStudentsForGroupAssignment(groupId);
            showAddStudentModal(groupId);
        } else {
            console.error('Group ID is not defined.');
        }
    } else {
        console.error('No group is selected.');
    }
});

function populateStudentsAndGoals() {
    const studentList = document.getElementById('student-list');
    const studentsMessage = document.getElementById('students-message');
    if (studentList.children.length > 0) {
        studentsMessage.style.display = 'none';
        studentList.style.display = 'block';
    } else {
        studentsMessage.style.display = 'block';
        studentList.style.display = 'none';
    }

    const goalList = document.getElementById('goal-list');
    const goalsMessage = document.getElementById('goals-message');
    if (goalList.children.length > 0) {
        goalsMessage.style.display = 'none';
        goalList.style.display = 'block';
    } else {
        goalsMessage.style.display = 'block';
        goalList.style.display = 'none';
    }
}

function addGroup(event) {
    event.preventDefault();
    const groupName = document.getElementById('group-name').value;

    fetch('./users/add_group.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'create_group=1&group_name=' + encodeURIComponent(groupName)
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.text();
        })
        .then(data => {
            //console.log('Group added successfully:', data);
            loadGroups();
            hideAddGroupModal();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('There was an error adding the group. Please try again.');
        });
}

function addStudent(event) {
    event.preventDefault();
    const firstName = document.getElementById('first-name').value;
    const lastName = document.getElementById('last-name').value;
    const dateOfBirth = document.getElementById('date-of-birth').value;
    const gradeLevel = document.getElementById('grade-level').value;
    const groupId = document.getElementById('group-select').value;

    fetch('./users/add_student.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `first_name=${encodeURIComponent(firstName)}&last_name=${encodeURIComponent(lastName)}&date_of_birth=${encodeURIComponent(dateOfBirth)}&grade_level=${encodeURIComponent(gradeLevel)}&group_id=${encodeURIComponent(groupId)}`
    })
    .then(response => response.json())
    .then(data => {
        //console.log('Student added successfully:', data);
        if (data.status === 'success') {
            loadStudents();
            hideAddStudentModal();
        } else {
            alert('Error adding student: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error adding the student. Please try again.');
    });
}

function loadStaff() {
    fetch('users/fetch_staff.php')
        .then(response => response.json())
        .then(data => {
            const staffSelect = document.getElementById('share-teacher-id');
            staffSelect.innerHTML = '<option value="">Select staff here</option>'; // Clear previous options

            data.forEach(staff => {
                // Populate select options
                const option = document.createElement('option');
                option.value = staff.teacher_id;
                option.textContent = `${staff.fname} ${staff.lname}`; // Correctly concatenate first name and last name
                staffSelect.appendChild(option);
            });

            // Reinitialize the select2 element if needed
            if ($.fn.select2) {
                $('.select2').select2();
            } else {
                console.warn("Select2 is not defined, ensure Select2 library is correctly included.");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('There was an error loading staff. Please try again.');
        });
}

// Function to show the modal
function showAddGroupModal() {
        const modal = document.getElementById('add-group-modal');
        if (modal) {
            modal.style.display = 'block';
        } else {
            console.error("Modal element not found");
        }
}

function showAddStudentModal(groupId) {
    //console.log('showAddStudentModal called with groupId:', groupId); // Debug log
    document.getElementById('add-student-modal').style.display = 'block';

    // Load students for the selected group
    loadGroupStudents(groupId, 'group-students-list-add');
}

// Function to hide the modal
function hideAddGroupModal() {
    const modal = document.getElementById('add-group-modal');
        if (modal) {
            modal.style.display = 'none';
        }
}

function hideAddStudentModal() {
        const modal = document.getElementById('add-student-modal');
        if (modal) {
            modal.style.display = 'none';
        }
}

function loadStudentsByGroup(groupId) {
    //console.log('Loading students for group by ID:', groupId); // Debug log

    fetch(`users/fetch_group_students.php?group_id=${encodeURIComponent(groupId)}`)
        .then(response => response.json())
        .then(data => {
            //console.log('Fetched group students:', data); // Debug log

            const studentList = document.getElementById('student-list');
            const studentsMessage = document.getElementById('students-message');
            
            if (data.error) {
                alert(data.error);
                return;
            }

            studentList.innerHTML = ''; // Clear the existing list

            if (data.length === 0) {
                studentsMessage.style.display = 'block';
                studentList.style.display = 'none';
                studentsMessage.innerHTML = 'No students in this group.';
                return;
            }

            // Sort students by last name within the group
            data.sort((a, b) => a.last_name.localeCompare(b.last_name));

            data.forEach(student => {
                const listItem = document.createElement('li');
                listItem.textContent = student.first_name + ' ' + student.last_name;
                listItem.setAttribute('data-student-id', student.student_id);
                listItem.addEventListener('click', () => selectStudent(listItem)); // Add event listener
                studentList.appendChild(listItem);
            });

            studentsMessage.style.display = 'none';
            studentList.style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching group students:', error);
            alert('There was an error loading the students for this group. Please try again.');
        });
}

function loadStudentsForGroupAssignment(groupId) {
    //console.log('Loading students for group assignment:', groupId); // Debug log

    fetch(`users/fetch_students.php?group_id=${encodeURIComponent(groupId)}`)
        .then(response => response.json())
        .then(data => {
            //console.log('Fetched students:', data); // Debug log

            if (Array.isArray(data)) {
                const studentSelect = document.querySelector('[name="student_id"]');
                studentSelect.innerHTML = '<option></option>'; // Clear previous options

                // Sort students by last name
                data.sort((a, b) => a.last_name.localeCompare(b.last_name));

                data.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.student_id_new;
                    option.textContent = student.first_name + ' ' + student.last_name;
                    studentSelect.appendChild(option);
                });

                // Reinitialize the select2 element
                $('.select2').select2();
            } else {
                console.error('Expected an array but received:', data);
                alert('There was an error loading students. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error fetching students for assignment:', error);
            alert('There was an error loading students. Please try again.');
        });
}

function loadGroups() {
    fetch('users/fetch_groups.php')
        .then(response => response.json())
        .then(data => {
            const groupList = document.getElementById('group-list').querySelector('ul');
            groupList.innerHTML = '';
            data.forEach(group => {
                const listItem = document.createElement('li');
                listItem.textContent = group.group_name;
                listItem.setAttribute('data-group-id', group.group_id);
                listItem.setAttribute('data-group-name', group.group_name); // Ensure this is set
                listItem.addEventListener('click', function() {
                    selectGroup(this);
                });

                const optionsBtn = document.createElement('button');
                optionsBtn.className = 'options-btn';
                optionsBtn.addEventListener('click', function(event) {
                    showGroupOptions(event, group.group_id, group.group_name); // Pass group name here
                });

                listItem.appendChild(optionsBtn);
                groupList.appendChild(listItem);
            });

            // Call populateStudentsAndGoals after updating the group list
            populateStudentsAndGoals();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('There was an error loading groups. Please try again.');
        });
}

function loadStudents() {
    fetch('users/fetch_students.php') // Adjust the endpoint if necessary
        .then(response => response.json())
        .then(data => {
            const studentList = document.getElementById('student-list');
            const studentsMessage = document.getElementById('students-message');
            const studentSelect = document.querySelector('[name="student_ids[]"]');
            studentList.innerHTML = '';
            studentSelect.innerHTML = '<option></option>'; // Clear previous options

            // Sort students by last name
            data.sort((a, b) => a.last_name.localeCompare(b.last_name));

            data.forEach(student => {
                // Populate student list
                const listItem = document.createElement('li');
                listItem.textContent = student.first_name + ' ' + student.last_name;
                listItem.setAttribute('data-student-id', student.student_id_new);
                listItem.addEventListener('click', function() {
                    selectStudent(this);
                });
                studentList.appendChild(listItem);

                // Populate select options
                const option = document.createElement('option');
                option.value = student.student_id_new;
                option.textContent = student.first_name + ' ' + student.last_name;
                studentSelect.appendChild(option);
            });

            // Reinitialize the select2 element
            $('.select2').select2();

            // Show or hide the student list and message based on content
            if (studentList.children.length > 0) {
                studentsMessage.style.display = 'none';
                studentList.style.display = 'block';
            } else {
                studentsMessage.style.display = 'block';
                studentList.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('There was an error loading students. Please try again.');
        });
}

function selectGroup(element) {
    const groupId = element.getAttribute('data-group-id');
    
    // Update the hidden input with the selected groupId
    document.getElementById('edit-group-id').value = groupId;

    // Load students for the selected group
    loadStudentsByGroup(groupId);

    const groupItems = document.getElementById('group-list').querySelectorAll('li');
    groupItems.forEach(group => group.classList.remove('selected-group'));
    element.classList.add('selected-group');
}

function selectStudent(element) {
    const studentId = element.getAttribute('data-student-id');
    document.getElementById('selected-student-id').value = studentId;

    loadGoals(studentId);

    const studentItems = document.getElementById('student-list').querySelectorAll('li');
    studentItems.forEach(student => student.classList.remove('selected-student'));
    element.classList.add('selected-student');
}

function showGroupOptions(event, groupId, groupName) {
    event.stopPropagation();
    const optionsMenu = document.getElementById('group-options');
    optionsMenu.style.display = 'block';
    optionsMenu.style.left = event.pageX + 'px';
    optionsMenu.style.top = event.pageY + 'px';
    optionsMenu.setAttribute('data-group-id', groupId);
    optionsMenu.setAttribute('data-group-name', groupName);
}

function editGroup() {
    const groupId = document.getElementById('group-options').getAttribute('data-group-id');
    const groupName = document.getElementById('group-options').getAttribute('data-group-name');
    showEditGroupModal(groupId, groupName);
}

function updateGroup(event) {
    event.preventDefault();

    const groupId = document.getElementById('edit-group-id').value;
    const groupName = document.getElementById('edit-group-name').value;

    if (!groupId || !groupName) {
        alert("Group ID and name are required.");
        return;
    }

    fetch('./users/update_group.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `group_id=${encodeURIComponent(groupId)}&group_name=${encodeURIComponent(groupName)}`
    })
    .then(response => response.text())
    .then(data => {
        //console.log('Response:', data); // Debug log

        if (data.includes('Group updated successfully')) {
            alert('Group updated successfully.');
            hideEditGroupModal();
            loadGroups(); // Reload the groups to reflect the updated name
        } else {
            alert('Error updating group: ' + data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error updating the group. Please try again.');
    });
}

function assignStudentsToGroup(event) {
    event.preventDefault();

    const groupId = document.getElementById('edit-group-id').value;
    const studentIds = Array.from(document.querySelector('[name="student_id"]').selectedOptions).map(option => option.value);

    if (!groupId || studentIds.length === 0) {
        alert("Please select a student and a group.");
        return;
    }

    //console.log('Assigning students to group:', groupId, studentIds); // Debug log

    fetch('./users/assign_students_to_group.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `group_id=${encodeURIComponent(groupId)}&student_ids=${encodeURIComponent(studentIds.join(','))}`
    })
    .then(response => response.json())
    .then(data => {
        //console.log('Response:', data); // Debug log
        if (data.status === "success") {
            //alert(data.message);
            loadGroupStudents(groupId, 'group-students-list-add'); // Reload the student list
            //console.log('Student assigned, now reloading group students for groupId:', groupId); // Debug log
        } else {
            alert(data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error assigning students to the group. Please try again.');
    });
}

function shareGroup(event) {
    event.preventDefault();
    const groupId = document.getElementById('share-group-id').value;
    const teacherId = document.getElementById('share-teacher-id').value;

    fetch('users/share_group.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `group_id=${encodeURIComponent(groupId)}&shared_teacher_id=${encodeURIComponent(teacherId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            alert(data.message);
        }
        hideEditGroupModal();
        loadGroups();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error sharing the group. Please try again.');
    });
}

function editGoal(goalId) {
    const editor = document.querySelector(`.quill-editor[data-goal-id="${goalId}"]`);
    const quill = quillInstances[goalId];
    quill.enable(true);
    quill.root.setAttribute('contenteditable', true);

    // Remove any existing save buttons
    document.querySelectorAll('.save-btn').forEach(btn => btn.remove());

    const saveBtn = document.createElement('button');
    saveBtn.textContent = 'Save';
    saveBtn.className = 'save-btn';
    saveBtn.onclick = function() {
        saveGoal(goalId, quill.root.innerHTML);
    };
    editor.parentNode.appendChild(saveBtn);
}

function saveGoal(goalId, goalDescription) {
    fetch('users/fetch_goals.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `goal_id=${encodeURIComponent(goalId)}&goal_description=${encodeURIComponent(goalDescription)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            const quill = quillInstances[goalId];
            quill.enable(false);
            quill.root.setAttribute('contenteditable', false);
            document.querySelector(`.quill-editor[data-goal-id="${goalId}"]`).parentNode.querySelector('.save-btn').remove();
            alert('Goal updated successfully.');
        } else {
            alert('There was an error updating the goal. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error updating the goal. Please try again.');
    });
}

function showEditGroupModal(groupId, groupName) {
    //console.log('showEditGroupModal called with groupId:', groupId, 'and groupName:', groupName); // Debug log
    document.getElementById('edit-group-id').value = groupId;
    document.getElementById('edit-group-name').value = groupName || '';
    document.getElementById('edit-group-modal').style.display = 'block';

    // Load students for the selected group
    //loadGroupStudents(groupId, 'group-students-list-edit');
}

function hideEditGroupModal() {
    document.getElementById('edit-group-modal').style.display = 'none';
    resetStudentList();

    // Show the Edit Group button
    const editGroupButton = document.querySelector('.edit-group-btn');
    if (editGroupButton) {
        editGroupButton.style.display = 'inline-block';
    }
}

function loadGroupStudents(groupId, targetElementId = 'group-students-list-add') {
    //console.log('Loading students for group:', groupId); // Debug log

    fetch(`./users/fetch_group_students.php?group_id=${encodeURIComponent(groupId)}`)
        .then(response => response.json())
        .then(data => {
            //console.log('Fetched group students:', data); // Debug log

            const groupStudentsList = document.getElementById(targetElementId);
            if (!groupStudentsList) {
                console.error('Target element not found:', targetElementId);
                return;
            }

            groupStudentsList.innerHTML = '';

            if (data.error) {
                alert(data.error);
                return;
            }

            if (data.length === 0) {
                groupStudentsList.innerHTML = '<p>No students in this group.</p>';
                return;
            }

            // Sort students by last name
            data.sort((a, b) => a.last_name.localeCompare(b.last_name));

            data.forEach(student => {
                const studentItem = document.createElement('div');
                studentItem.style.display = 'flex';
                studentItem.style.alignItems = 'center';
                studentItem.style.marginBottom = '10px';

                const studentName = document.createElement('span');
                studentName.style.marginRight = '10px';
                studentName.textContent = student.name;

                const removeButton = document.createElement('button');
                removeButton.style.color = 'red';
                removeButton.style.background = 'none';
                removeButton.style.border = 'none';
                removeButton.style.cursor = 'pointer';
                removeButton.style.fontSize = '16px';
                removeButton.style.lineHeight = '1';
                removeButton.textContent = '×';
                removeButton.onclick = () => removeStudentFromGroup(student.student_id, groupId);

                studentItem.appendChild(studentName);
                studentItem.appendChild(removeButton);
                groupStudentsList.appendChild(studentItem);
            });

            //console.log('Updated DOM with new student list.');
        })
        .catch(error => {
            console.error('Error fetching group students:', error);
            alert('There was an error loading the students for this group. Please try again.');
        });
}

function removeStudentFromGroup(studentId, groupId) {
    if (!confirm('Are you sure you want to remove this student from the group?')) {
        return;
    }

    fetch('users/remove_student_from_group.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `student_id=${encodeURIComponent(studentId)}&group_id=${encodeURIComponent(groupId)}`
    })
    .then(response => response.json())
    .then(data => {
        //console.log('Remove student response data:', data); // Debugging statement
        if (data.status === 'success') {
            //alert('Student removed from group successfully.');
            //console.log('Student removed, now reloading group students for groupId:', groupId);
            setTimeout(() => {
                loadGroupStudents(groupId, 'group-students-list-add'); // Explicitly target the correct element
                //console.log('Reloaded group students for groupId:', groupId); // Debugging statement
            }, 0); // Adding a slight delay to ensure the list updates
        } else {
            alert('There was an error removing the student from the group. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error removing student from group:', error);
        alert('There was an error removing the student from the group. Please try again.');
    });
}

function loadAllStudentsForAssignment(groupId) {
    fetch('users/fetch_students.php') // Adjust the endpoint if necessary
        .then(response => response.json())
        .then(data => {
            const studentSelect = document.querySelector('[name="student_id"]');
            studentSelect.innerHTML = '<option></option>'; // Clear previous options

            // Filter students who are not in the selected group
            const filteredStudents = data.filter(student => !student.groups.includes(groupId));

            filteredStudents.forEach(student => {
                const option = document.createElement('option');
                option.value = student.student_id_new;
                option.textContent = student.first_name + ' ' + student.last_name;
                studentSelect.appendChild(option);
            });

            // Reinitialize the select2 element
            $('.select2').select2();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('There was an error loading students. Please try again.');
        });
}

function deleteGroup() {
    const confirmDeletion = confirm('Are you sure you want to delete this group?');
    if (!confirmDeletion) {
        return;
    }

    const groupId = document.getElementById('edit-group-id').value;

    if (!groupId) {
        alert('Group ID is not defined.');
        return;
    }

    fetch('users/delete_group.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `group_id=${encodeURIComponent(groupId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            hideEditGroupModal();
            loadGroups();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error deleting the group. Please try again.');
    });
}

function resetStudentList() {
    const studentList = document.getElementById('student-list');
    const selectedGroup = document.querySelector('.selected-group');

    if (selectedGroup) {
        const groupId = selectedGroup.getAttribute('data-group-id');
        loadStudentsByGroup(groupId);
    } else {
        studentList.innerHTML = '<p>Please select a group to view students.</p>';
    }
}

function loadMetadata() {
    fetch('users/fetch_metadata.php')
        .then(response => response.json())
        .then(data => {
            const metadataSelect = document.getElementById('existing-metadata-id');
            if (metadataSelect) {
                metadataSelect.innerHTML = '';

                data.forEach(metadata => {
                    const option = document.createElement('option');
                    option.value = metadata.metadata_id;
                    option.textContent = metadata.category_name;
                    metadataSelect.appendChild(option);
                });
            } else {
                console.error('Metadata select element not found.');
            }
        })
        .catch(error => {
            console.error('Error loading metadata:', error);
            alert('There was an error loading metadata. Please try again.');
        });
}

function showAddGoalModal() {
    const selectedStudent = document.querySelector('.selected-student');
    if (!selectedStudent) {
        alert('Please select a student first.');
        return;
    }
    const modal = document.getElementById('add-goal-modal');
    modal.style.display = 'block';

    // Load templates and existing categories when modal is shown
    loadTemplates();
    loadExistingCategories();
}

function hideAddGoalModal() {
    const modal = document.getElementById('add-goal-modal');
    modal.style.display = 'none';
}

function addGoal(event) {
    event.preventDefault();

    const studentId = document.getElementById('selected-student-id').value;
    const goalDescription = document.getElementById('goal-description').value;
    const goalDate = document.getElementById('goal-date').value;
    const metadataOptionElement = document.querySelector('#metadataOptionSelector .selector-item.selected');
    const metadataOption = metadataOptionElement ? metadataOptionElement.getAttribute('data-option') : null;
    const schoolId = <?= json_encode($_SESSION['school_id']); ?>;
    let metadataId = null;

    if (!studentId || !goalDescription || !goalDate || !metadataOption || !schoolId) {
        alert('Missing required parameters.');
        return;
    }

    if (metadataOption === 'existing') {
        metadataId = document.getElementById('existing-metadata-select').value;
        if (!metadataId) {
            alert('Please select an existing category.');
            return;
        }
    } else if (metadataOption === 'template') {
        metadataId = document.getElementById('template-metadata-select').value;
        if (!metadataId) {
            alert('Please select a category template.');
            return;
        }

        // If using a template, copy the template to create a new metadata entry
        fetch(`users/fetch_metadata_details.php?metadata_id=${metadataId}`)
            .then(response => response.json())
            .then(template => {
                if (template.error) {
                    throw new Error(template.error);
                }

                return fetch('./users/add_goal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        student_id: studentId,
                        goal_description: goalDescription,
                        goal_date: goalDate,
                        metadata_option: metadataOption,
                        template_id: metadataId,
                        school_id: schoolId
                    })
                });
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }

                alert('Goal added successfully!');
                hideAddGoalModal();
                loadGoals(studentId); // Refresh the goals list
            })
            .catch(error => {
                console.error('Error adding goal:', error);
                alert('Error adding goal: ' + error.message);
            });

        return;
    } else {
        alert('Invalid metadata option.');
        return;
    }

    fetch('./users/add_goal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            student_id: studentId,
            goal_description: goalDescription,
            goal_date: goalDate,
            metadata_option: metadataOption,
            existing_category_id: metadataId,
            school_id: schoolId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }

        alert('Goal added successfully!');
        hideAddGoalModal();
        loadGoals(studentId); // Refresh the goals list
    })
    .catch(error => {
        console.error('Error adding goal:', error);
        alert('Error adding goal: ' + error.message);
    });
}

// Add the loadGoals function definition somewhere in your script
function loadGoals(studentId) {
    fetch(`users/fetch_goals.php?student_id=${encodeURIComponent(studentId)}`)
        .then(response => response.text())
        .then(data => {
            //console.log('Raw response:', data);
            try {
                const jsonData = JSON.parse(data.trim());
                if (jsonData.error) {
                    alert(jsonData.message);
                    return;
                }

                const goalList = document.getElementById('goal-list');
                goalList.innerHTML = '';

                const goalsByMetadata = jsonData.reduce((acc, goal) => {
                    if (!acc[goal.metadata_id]) {
                        acc[goal.metadata_id] = { category_name: goal.category_name, goals: [] };
                    }
                    acc[goal.metadata_id].goals.push(goal);
                    return acc;
                }, {});

                for (const metadataId in goalsByMetadata) {
                    const metadataGoals = goalsByMetadata[metadataId];

                    const metadataContainer = document.createElement('div');
                    const metadataLink = document.createElement('a');
                    metadataLink.href = `student_data.php?student_id=${studentId}&metadata_id=${metadataId}`;
                    metadataLink.innerHTML = `<h4 class="goal-category">${metadataGoals.category_name}</h4>`;
                    metadataContainer.appendChild(metadataLink);

                    metadataGoals.goals.forEach(goal => {
                        if (!goal.archived) {
                            const listItem = document.createElement('div');
                            listItem.classList.add('goal-item');
                            listItem.innerHTML = `
                                <div class="goal-content">
                                    <div class="quill-editor" data-goal-id="${goal.goal_id}">${goal.goal_description}</div>
                                    <button class="edit-btn" onclick="editGoal(${goal.goal_id})">✏️</button>
                                    <button class="archive-btn" onclick="archiveGoal(${goal.goal_id})">Archive</button>
                                </div>
                                <div class="progress-reports">
                                    <strong>Progress Reports:</strong>
                                    <div class="thumbnails">
                                    ${goal.notes.map((note, index) => note.report_image ? `
                                        <div class="thumbnail-container">
                                            <a href="${note.report_image}" data-lightbox="goal-${goal.goal_id}" data-title="Report Image">
                                                <img src="${note.report_image}" alt="Report Available" class="thumbnail">
                                                <div class="thumbnail-overlay">${index + 1}</div>
                                            </a>
                                        </div>
                                    ` : '').join('')}
                                    </div>
                                </div>
                            `;

                            metadataContainer.appendChild(listItem);
                        }
                    });

                    goalList.appendChild(metadataContainer);
                }

                document.querySelectorAll('.quill-editor').forEach(editor => {
                    const goalId = editor.getAttribute('data-goal-id');
                    if (!quillInstances[goalId]) {
                        quillInstances[goalId] = new Quill(editor, {
                            theme: 'snow',
                            readOnly: true,
                            modules: {
                                toolbar: false
                            }
                        });
                    }
                });
            } catch (error) {
                console.error('Error parsing JSON:', error);
                alert('Error processing the goals. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('There was an error fetching goals. Please try again.');
        });
}

function archiveGoal(goalId) {
    if (!confirm('Are you sure you want to archive this goal?')) {
        return;
    }

    fetch('./users/archive_goal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `goal_id=${encodeURIComponent(goalId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Goal archived successfully.');
            loadGoals(document.getElementById('selected-student-id').value);
        } else {
            alert('Error archiving goal: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error archiving goal:', error);
        alert('Error archiving goal: ' + error.message);
    });
}

function toggleMetadataOption() {
    const templateOption = document.querySelector('input[name="metadata_option"][value="template"]').checked;
    const existingOption = document.querySelector('input[name="metadata_option"][value="existing"]').checked;

    document.getElementById('template-dropdown').style.display = templateOption ? 'block' : 'none';
    document.getElementById('existing-dropdown').style.display = existingOption ? 'block' : 'none';
}

// Function to load metadata templates
function loadTemplates() {
    fetch('users/fetch_metadata_templates.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }

            const templateSelect = document.getElementById('template-metadata-select');
            if (!templateSelect) {
                console.error('Template metadata select element not found.');
                return;
            }
            templateSelect.innerHTML = '<option value="">Select a category to see column options</option>';

            data.forEach(template => {
                const option = document.createElement('option');
                option.value = template.metadata_id;
                option.textContent = template.category_name;
                templateSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading metadata templates:', error);
        });
}

function loadExistingCategories() {
    const studentId = document.getElementById('selected-student-id').value;
    const schoolId = <?= json_encode($_SESSION['school_id']); ?>;

    fetch(`users/fetch_existing_categories.php?student_id=${studentId}&school_id=${schoolId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }

            const existingSelect = document.getElementById('existing-metadata-select');
            if (!existingSelect) {
                console.error('Existing metadata select element not found.');
                return;
            }
            existingSelect.innerHTML = '<option value="">Select a category to see column options</option>';

            data.forEach(category => {
                const option = document.createElement('option');
                option.value = category.metadata_id;
                option.textContent = category.category_name;
                existingSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading existing categories:', error);
        });
}

function showColumnNames(type) {
    let selectedId;
    if (type === 'template') {
        selectedId = document.getElementById('template-metadata-select').value;
    } else if (type === 'existing') {
        selectedId = document.getElementById('existing-metadata-select').value;
    }

    if (!selectedId) {
        document.getElementById('columnNamesDisplay').style.display = 'none';
        return;
    }

    fetch(`users/fetch_metadata_details.php?metadata_id=${selectedId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }

            const columnNamesList = document.getElementById('columnNamesList');
            if (!columnNamesList) {
                console.error('Column names list element not found.');
                return;
            }
            columnNamesList.innerHTML = '';

            for (let i = 1; i <= 10; i++) {
                const scoreName = data[`score${i}_name`];
                if (scoreName) {
                    const listItem = document.createElement('li');
                    listItem.textContent = scoreName;
                    columnNamesList.appendChild(listItem);
                }
            }

            document.getElementById('columnNamesDisplay').style.display = 'block';
        })
        .catch(error => {
            console.error('Error loading column names:', error);
        });
}
</script>
</body>
</html>
