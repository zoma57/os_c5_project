# 🚀 OS CPU Scheduling Simulator (Project C5)
**Comparative Analysis: Round Robin (RR) vs. Preemptive SJF (SRTF)**

> 🎓 **Academic Project:** Submitted for the Operating Systems Course at the Faculty of Computers and Artificial Intelligence - Helwan University.

---

## 📌 Project Overview
This project is a web-based CPU Scheduling Simulator designed to compare the performance, efficiency, and fairness of two fundamental scheduling algorithms:
1. **Round Robin (RR)** 2. **Shortest Job First (SJF) - Preemptive Mode (SRTF)**

The simulator provides a dynamic Graphical User Interface (GUI) to input process details, calculates standard OS metrics, and visualizes the execution flow using generated Gantt Charts.

---

## ⚠️ Key Assumptions & Rubric Compliance
As per the project's specific notes and evaluation rubric:
* **Preemptive SJF:** We implemented the *Preemptive* version of SJF (also known as SRTF). If a new process arrives with a shorter remaining burst time than the currently running process, the CPU will preempt the current process.
* **Unified Workload:** The simulator strictly evaluates both algorithms against the *exact same workload* simultaneously to ensure a 100% fair comparison.
* **Context Switching:** For the scope of this simulation, context switching time is assumed to be `0`.

---

## 🏗️ Project Architecture & Repository Structure
To maintain the "Separation of Concerns" principle, the project is structured using an MVC-like pattern:

    os_c5_project/
    │
    ├── src/
    │   ├── model/
    │   │   └── Process.php          # Defines the Process object
    │   ├── scheduler/
    │   │   ├── RRScheduler.php      # Round Robin logic
    │   │   └── SJFScheduler.php     # Preemptive SJF / SRTF logic
    │   └── gui/
    │       ├── index.php            # Dynamic HTML/JS input form 
    │       └── simulator.php        # Controller & Gantt Chart rendering
    │
    ├── screenshots/                 # Visual evidence of execution
    ├── test-cases/                  # Documented test scenarios
    └── README.md                    # Project documentation

---

## 📊 Calculated Metrics
For every simulation, the system accurately computes:
* **Response Time (RT):** Time from arrival to the first CPU access.
* **Completion Time (CT):** The exact time the process terminates.
* **Turnaround Time (TAT):** Total time spent in the system (`CT - Arrival Time`).
* **Waiting Time (WT):** Total time spent waiting in the ready queue (`TAT - Burst Time`).
* **Averages:** Computes Average WT and Average TAT to conclude the efficiency vs. fairness trade-off.

---

## 💻 Technologies Used
* **Backend Logic:** `PHP 8+` (Chosen for robust array manipulation and server-side logic).
* **Frontend/GUI:** `HTML5`, `CSS3`, `Vanilla JavaScript` (For dynamic row additions and test scenario loading).

---

## 🚀 How to Run the Project (Local Environment)
Since this project relies on server-side PHP processing, it requires a local server environment.

1. **Install XAMPP:** Download and install XAMPP.
2. **Start Apache:** Open the XAMPP Control Panel and start the `Apache` module.
3. **Clone the Repository:** Clone or extract this project folder into the `htdocs` directory. (Path: `C:\xampp\htdocs\os_c5_project`)
4. **Run in Browser:** Open your web browser and navigate to: `http://localhost/os_c5_project/src/gui/index.php`

---

## 🧪 Included Test Scenarios
To demonstrate the strengths and weaknesses of each algorithm, we included built-in test scenarios in the GUI:

1. **Scenario A (Normal Case):** Standard varied arrivals to check basic correctness.
2. **Scenario B (Starvation Proof):** Introduces a long process followed by continuous short processes. Preemptive SJF causes the long process to suffer from starvation (High WT), while RR ensures fair execution.
3. **Scenario C (Simultaneous Arrival):** All processes arrive at `Time = 0`. Demonstrates how RR divides the CPU fairly, acting as a true time-sharing system.

---

## 🧠 Conclusion: Fairness vs. Efficiency Trade-off
Based on our simulation results:
* **Preemptive SJF (SRTF)** provides maximum *efficiency* by yielding the lowest Average Waiting Time. However, it severely lacks *fairness*, as longer jobs can face infinite delays (Starvation).
* **Round Robin (RR)** guarantees *fairness* and an excellent Response Time for all processes, but at the cost of slightly higher average waiting times.

---

- [Name 1] - [ID 1] - Contribution: Scheduling Logic
> 🎓 **Academic Project:** Submitted for the Operating Systems Course at the Faculty of Computers and Artificial Intelligence - Helwan University.
- [Name 2] - [ID 2] - Contribution: GUI & Interface

- [Name 3] - [ID 3] - Contribution: Metrics Calculation
---
- [Name 4] - [ID 4] - Contribution: Validation & Security

- [Name 5] - [ID 5] - Contribution: Auto-Analysis Engine
## 📌 Project Overview
- [Name 6] - [ID 6] - Contribution: Documentation & GitHub
This project is a web-based CPU Scheduling Simulator designed to compare the performance, efficiency, and fairness of two fundamental scheduling algorithms:
- [Name 7] - [ID 7] - Contribution: Test Scenarios
