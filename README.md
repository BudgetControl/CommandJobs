# BudgetControl Ms template

This repository contains the code of microservice budgetcontrol jobs client application.

## Prerequisites

- Docker: [Install Docker](https://docs.docker.com/get-docker/)
- Task: [Install Task](https://taskfile.dev/#/installation)

## Getting Started

1. Clone this repository:

    ```bash
    git clone https://github.com/BudgetControl/CommandJobs.git
    ```

2. Build and run the Docker containers:

    ```bash
    task build:dev
    ```

## Task Commands

- `task build:dev`: Install and build dev application.
- `task run:db`: Build and run dev database

### Test with mailhog service
You can use an fake mailhog server
- docker run --rm -d --name mailhog -p 8025:8025 -p 1025:1025 mailhog/mailhog
- docker network connect [network_name] mailhog

## Contributing

Contributions are welcome! Please read our [Contribution Guidelines](CONTRIBUTING.md) for more information.

## License

This project is licensed under the [MIT License](LICENSE).