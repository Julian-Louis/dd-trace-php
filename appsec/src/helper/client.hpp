// Unless explicitly stated otherwise all files in this repository are
// dual-licensed under the Apache-2.0 License or BSD-3-Clause License.
//
// This product includes software developed at Datadog
// (https://www.datadoghq.com/). Copyright 2021 Datadog, Inc.
#pragma once

#include <cstdint>

#include "config.hpp"
#include "engine.hpp"
#include "network/broker.hpp"
#include "network/proto.hpp"
#include "network/socket.hpp"
#include "service_manager.hpp"
#include "worker_pool.hpp"
#include <optional>

namespace dds {

class client {
public:
    // Below this limit the encoding+compression might result on a longer string
    client(std::shared_ptr<service_manager> service_manager,
        std::unique_ptr<network::base_broker> &&broker)
        : broker_(std::move(broker)),
          service_manager_(std::move(service_manager))
    {}

    client(std::shared_ptr<service_manager> service_manager,
        std::unique_ptr<network::base_socket> &&socket)
        : broker_(std::make_unique<network::broker>(std::move(socket))),
          service_manager_(std::move(service_manager))
    {}

    ~client() = default;
    client(const client &) = delete;
    client &operator=(const client &) = delete;
    client(client &&) = delete;
    client &operator=(client &&) = delete;

    bool handle_command(const network::client_init::request &);
    // NOLINTNEXTLINE(google-runtime-references)
    bool handle_command(network::request_init::request &);
    // NOLINTNEXTLINE(google-runtime-references)
    bool handle_command(network::request_exec::request &);
    // NOLINTNEXTLINE(google-runtime-references)
    bool handle_command(network::request_shutdown::request &);
    // NOLINTNEXTLINE(google-runtime-references)
    bool handle_command(network::config_sync::request &);

    bool run_client_init();
    bool run_request();

    [[nodiscard]] std::shared_ptr<service> get_service() const
    {
        return service_;
    }

    // NOLINTNEXTLINE(google-runtime-references)
    void run(worker::queue_consumer &q);
    bool compute_client_status();

    void update_remote_config_path(std::string_view path);

protected:
    void set_service(std::shared_ptr<service> new_service)
    {
        if (!new_service) {
            sample_acc_.reset();
            service_.reset();
            return;
        }

        std::optional<sampler> &sampler = new_service->get_schema_sampler();
        sample_acc_ =
            sampler.has_value() ? sampler->new_accessor_up() : nullptr;

        // must come after, sample_acc_ depends on service_
        service_ = std::move(new_service);
    }

    template <typename T>
    std::shared_ptr<typename T::response> publish(
        typename T::request &command, const std::string &rasp_rule = "");
    template <typename T> bool service_guard();
    template <typename T, bool actions = true>
    bool send_message(const std::shared_ptr<typename T::response> &message);
    bool initialised{false};
    uint32_t version{};
    std::unique_ptr<network::base_broker> broker_;
    std::shared_ptr<service_manager> service_manager_;
    std::optional<dds::engine_settings> engine_settings_;

    std::shared_ptr<service> service_;
    std::unique_ptr<sampler::table_accessor> sample_acc_; // depends on service_

    std::optional<engine::context> context_;
    std::optional<bool> client_enabled_conf;
    bool request_enabled_ = {false};
    sidecar_settings sc_settings_;
};

} // namespace dds
