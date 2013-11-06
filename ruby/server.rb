#!/usr/local/bin/ruby
require "socket"
require 'json'
Thread.abort_on_exception = true

gs = TCPServer.new('*', 30000)
Thread.current["name"]="Main"
room = Array.new

socks = [gs]

observer=Thread.new {
Thread.current["name"] = "Observer"
loop do
        sleep 1
        socks.each do |s|
                if s!=gs
                        s.puts("additional element")
                end
        end
end
}

while true
  nsock = select(socks)
  next if nsock == nil
  for s in nsock[0]
    if s == gs
      socks.push(s.accept)
    else
        begin
      if s.eof?
        s.close
        socks.delete(s)
      else
        str = s.gets
        if Random.rand(2)==1
                s.puts(str)
        end
      end
        rescue Exception => exception
          case exception
            when Errno::ECONNRESET,Errno::ECONNABORTED,Errno::ETIMEDOUT
              socks.delete(s)
            else
              raise exception
          end
        end
    end
  end
end