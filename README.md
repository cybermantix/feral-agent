# Feral Agent

## Overview
Feral Agent is an Open Source AI Agent framework that enables the creation and execution of AI-driven processes. The framework is designed around a collection of **Catalog Nodes**, which are dynamically assembled into workflows. Each node's **Result** determines the next node to execute, ensuring an adaptive and context-aware process.

A **Context** object is passed through the process, providing the necessary data for decision-making at each node. This approach enables flexible, AI-driven execution strategies, making Feral Agent ideal for autonomous workflow generation and AI agent integrations.

## Modes of Operation
Feral Agent operates in two distinct modes:

### 1. Tool Mode
In **Tool Mode**, multiple predefined processes are hardcoded into the system. The AI Agent selects and executes a process based on its interaction with a Large Language Model (LLM). This mode is useful when there are well-defined workflows that should be executed deterministically.

- Processes are manually defined and structured.
- The AI Agent chooses which predefined process to run based on context.
- Ensures controlled execution of structured tasks.

### 2. Autonomous Mode
In **Autonomous Mode**, the system dynamically constructs workflows using an LLM. Instead of selecting from predefined processes, the AI generates a process from scratch based on available **Catalog Nodes** and their descriptions.

- All available Catalog Nodes and their descriptions are sent to the LLM.
- The LLM generates the process flow, determining result connections and the initial context.
- The process is built dynamically, adapting to the situation and input context.

This mode allows for highly flexible, self-assembling workflows, enabling AI-driven decision-making without requiring predefined structures.

## Getting Started
To use Feral Agent, you can either:
- Run predefined workflows in **Tool Mode**.
- Let the AI generate adaptive workflows in **Autonomous Mode**.

More details on installation, configuration, and usage will be available in the full documentation.

## License
Feral Agent is open-source and available under the [Apache 2.0 License](LICENSE).

---
For contributions, issues, or discussions, visit the [Feral Agent Project Website](https://feralccf.com).

